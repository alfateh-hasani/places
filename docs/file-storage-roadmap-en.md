# File & Media Storage Roadmap (S3)

How uploads are organized today, the target structure, and a step-by-step plan to get there. Do this **now** while the `places-upload` bucket is nearly empty — migration cost grows with every new file.

---

## 1. Current state

- Storage engine: **spatie/laravel-medialibrary** (`MEDIA_DISK=s3`).
- Path strategy: **DefaultPathGenerator** → every file stored at:
  ```
  places-upload/{media_id}/{filename}
  ```
- Example: the direct-booking receipt → `places-upload/3590/emp_man.png` (3590 = media row id).
- `config/media-library.php` → `path_generator` and `custom_path_generators` are **defaults/empty**.

### Every collection in the app (source of truth)
| Model | Collection(s) | Sensitivity |
|---|---|---|
| Apartment | `image`, `video` | public |
| Building | `image` | public |
| Transaction | `receipt` | **private** (financial) |
| Customer | `profile` | public (avatars shown app-wide; kept public to avoid breaking cached API responses) |
| Slider | `image_ar`, `image_en` | public |
| SliderApp | `image_ar`, `image_en` | public |
| Blog | `image` | public |
| City | `image` | public |
| Page | `image` | public |
| Onboarding | `image` | public |
| Notification | `image` | public |
| SiteFeature | `icon` | public |
| Feature | `icon` | public |
| Advantage | `icon` | public |
| ApartmentLabel | `icon` | public |

### Problems
1. **Flat & unreadable** — all files in numbered folders at bucket root; can't browse or audit.
2. **Security hole** — the bucket policy is public-read for the *entire* bucket, so `Transaction/receipt` and `Customer/profile` files are publicly downloadable by URL. They must be private.
3. **No lifecycle control** — can't apply "expire temp files", "archive old receipts", etc. without a folder taxonomy.
4. **Hard to migrate/back up selectively.**

---

## 2. Target structure

Split by **public vs private** at the top level, then by domain → model → id → collection → media-id.

```
places-upload/
├── public/                         # bucket-policy public-read
│   ├── apartments/{id}/images/{media_id}/{file}
│   ├── apartments/{id}/videos/{media_id}/{file}
│   ├── buildings/{id}/images/{media_id}/{file}
│   ├── customers/{id}/profile/{media_id}/{file}
│   └── content/
│       ├── sliders/{id}/{collection}/{media_id}/{file}
│       ├── slider-apps/{id}/{collection}/{media_id}/{file}
│       ├── blogs/{id}/{media_id}/{file}
│       ├── pages/{id}/{media_id}/{file}
│       ├── cities/{id}/{media_id}/{file}
│       ├── onboarding/{id}/{media_id}/{file}
│       ├── notifications/{id}/{media_id}/{file}
│       └── icons/{features|site-features|advantages|apartment-labels}/{id}/{media_id}/{file}
│
└── private/                        # NO public read; served via signed temporary URLs
    └── transactions/{transaction_id}/receipts/{media_id}/{file}
```

Rules:
- `{media_id}` stays in the path — guarantees uniqueness and keeps spatie's move/delete working.
- Image conversions live beside the original under `.../{media_id}/conversions/`, responsive images under `.../{media_id}/responsive-images/` (handled automatically).
- New models slot in by adding one line to the map (see §3) — no bucket restructuring needed.

Why this shape:
- **Public/private split** fixes the security issue and lets one bucket policy target only `public/*`.
- **Domain → id** grouping makes browsing, lifecycle rules, and per-entity cleanup trivial.
- **Predictable & extendable** — every future collection follows the same pattern.

---

## 3. Implementation

### Step 1 — Custom PathGenerator
Create `app/Support/Media/DomainPathGenerator.php` implementing spatie's `PathGenerator`. One class, one map:

```php
<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class DomainPathGenerator implements PathGenerator
{
    /**
     * model_type => [visibility, prefix]. Prefix may use {collection}.
     *
     * @var array<class-string, array{0:string,1:string}>
     */
    private const MAP = [
        \App\Models\Apartment::class     => ['public',  'public/apartments/{id}/{collection}'],
        \App\Models\Building::class      => ['public',  'public/buildings/{id}/images'],
        \App\Models\Slider::class        => ['public',  'public/content/sliders/{id}/{collection}'],
        \App\Models\SliderApp::class     => ['public',  'public/content/slider-apps/{id}/{collection}'],
        \App\Models\Blog::class          => ['public',  'public/content/blogs/{id}'],
        \App\Models\Page::class          => ['public',  'public/content/pages/{id}'],
        \App\Models\City::class          => ['public',  'public/content/cities/{id}'],
        \App\Models\Onboarding::class    => ['public',  'public/content/onboarding/{id}'],
        \App\Models\Notification::class  => ['public',  'public/content/notifications/{id}'],
        \App\Models\Feature::class       => ['public',  'public/content/icons/features/{id}'],
        \App\Models\SiteFeature::class   => ['public',  'public/content/icons/site-features/{id}'],
        \App\Models\Advantage::class     => ['public',  'public/content/icons/advantages/{id}'],
        \App\Models\ApartmentLabel::class=> ['public',  'public/content/icons/apartment-labels/{id}'],
        \App\Models\Transaction::class   => ['private', 'private/transactions/{id}/receipts'],
        \App\Models\Customer::class      => ['private', 'private/customers/{id}/profile'],
    ];

    public function getPath(Media $media): string
    {
        return $this->basePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media).'/responsive-images/';
    }

    private function basePath(Media $media): string
    {
        $config = self::MAP[$media->model_type] ?? [null, 'misc/{id}'];

        $prefix = str_replace(
            ['{id}', '{collection}'],
            [(string) $media->model_id, $media->collection_name],
            $config[1],
        );

        return $prefix.'/'.$media->getKey();
    }
}
```

### Step 2 — Register it globally
`config/media-library.php`:
```php
'path_generator' => \App\Support\Media\DomainPathGenerator::class,
```

### Step 3 — Make private files actually private
Private files must NOT be publicly readable. Two parts:

a) **Bucket policy** — scope public-read to `public/*` only:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadForPublicPrefix",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::places-upload/public/*"
    }
  ]
}
```

b) **Serve private files via signed temporary URLs** instead of `getUrl()`:
```php
// receipt (private) — expiring link
$media->getTemporaryUrl(now()->addMinutes(15));

// apartment image (public) — normal permanent URL
$media->getUrl();
```
Audit every place that renders a receipt / customer profile and switch it to `getTemporaryUrl()`.

### Step 4 — Optional: clean file naming
Add a custom `file_namer` (or keep spatie's default) to slugify + lowercase filenames so you never get spaces/Arabic chars in keys. Nice-to-have, not required.

---

## 4. Migrating existing files

⚠️ Spatie computes the path from `media.id` + PathGenerator **at read time** — it does NOT store the full path in the DB. So the moment you switch PathGenerator, it looks for every file at its *new* location. Existing objects must be physically moved, or old URLs 404.

Because the bucket is nearly empty, pick one:

- **Option A (cleanest, do now):** move the handful of existing files, then switch. Write a one-off artisan command that iterates `Media::all()`, computes old path (`{id}/`) and new path (via the generator), and `Storage::disk('s3')->move($old, $new)` including `conversions/`.
- **Option B:** if the few existing files are throwaway test uploads, just delete them in S3 and re-upload after switching. Simplest.

Sketch for Option A (`php artisan media:relocate`):
```php
foreach (Media::cursor() as $media) {
    $disk = Storage::disk($media->disk);
    $oldDir = $media->id;                       // DefaultPathGenerator
    $newDir = rtrim(app(DomainPathGenerator::class)->getPath($media), '/');
    foreach ($disk->allFiles($oldDir) as $file) {
        $disk->move($file, str_replace($oldDir, $newDir, $file));
    }
}
```
(Run in a maintenance window; back up the media table first. **You run it — not automated.**)

---

## 5. S3 lifecycle & housekeeping (future)

Once the taxonomy exists, add S3 Lifecycle rules in the console:
- `private/transactions/*` → transition to cheaper storage (Standard-IA / Glacier) after N months; never auto-delete (financial records).
- Temp/scratch prefixes (if any added later) → expire after X days.
- Enable **versioning** on the bucket before bulk migrations so a bad move is recoverable.

---

## 6. Rollout order (checklist)

- [x] Add `DomainPathGenerator` class. → `app/Support/Media/DomainPathGenerator.php`
- [x] Register it in `config/media-library.php`.
- [x] Switch receipt rendering to a signed URL → `Transaction::receiptUrl()`, used in `BookingController`.
- [x] Relocation command written → `php artisan media:relocate` (run once).
- [ ] **AWS: split bucket policy → public-read only on `public/*`** (see §7 — do this in the S3 console).
- [ ] Run `php artisan media:relocate` (after `config:clear`) to move existing files.
- [ ] Test each upload type (apartment image, receipt, slider, icon) → verify path + access.
- [ ] Add S3 lifecycle rules.
- [ ] Repeat env + policy on production during server migration.

---

## 7. AWS S3 — set up public vs private access (step by step)

You do **not** create folders in S3 — the `public/` and `private/` prefixes appear automatically as files are uploaded. The only thing to configure is the **bucket policy** so `public/*` is world-readable and everything else (incl. `private/*`) is not.

1. AWS Console → **S3** → open bucket **`places-upload`**.
2. **Permissions** tab.
3. **Block public access (bucket settings)** → **Edit** → keep **all four boxes UNCHECKED** → Save (public-read is granted by the policy below; the private prefix stays private because the policy doesn't mention it).
4. Scroll to **Bucket policy** → **Edit** → replace the whole policy with this (scopes public read to `public/*` only):
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       {
         "Sid": "PublicReadForPublicPrefix",
         "Effect": "Allow",
         "Principal": "*",
         "Action": "s3:GetObject",
         "Resource": "arn:aws:s3:::places-upload/public/*"
       }
     ]
   }
   ```
5. **Save changes.**

How access now works:
- `public/...` → anyone with the URL can view (apartment images, sliders, icons, avatars). Served with normal `getUrl()`.
- `private/...` → direct URL returns **AccessDenied**. Served only through short-lived signed links via `Transaction::receiptUrl()` (presigned, expires in 15 min). No bucket/ACL change needed — the app's IAM key signs the link.

> Your bucket has **ACLs disabled** (bucket-owner-enforced), so access is controlled purely by this bucket policy — exactly what we want. Private objects need no per-file setting; they're private by simply not being matched by the policy.
