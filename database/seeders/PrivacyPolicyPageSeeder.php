<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Overwrites the Privacy Policy page content (content_ar / content_en) with the
 * finalized HTML. It UPDATES the existing page (matched by slug/template) — it does
 * not create a duplicate. The HTML is the exact markup CKEditor would store, so the
 * front renders it via {!! !!} and the admin editor loads it normally afterwards.
 *
 * Two placeholders remain for you to fill after seeding: the effective date and the
 * support email (marked with ⟦ ⟧). Run with:
 *   php artisan db:seed --class=PrivacyPolicyPageSeeder
 */
class PrivacyPolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::query()
            ->where('slug', 'privacy-policy')
            ->orWhere('template', 'privacy')
            ->first();

        if (! $page) {
            $this->command->warn('No privacy page found (slug "privacy-policy" or template "privacy"). Nothing updated.');

            return;
        }

        // Pull the support email from the site settings (same source the footer/contact
        // use) and the effective date as the run date, so no placeholders are left behind.
        //
        // Read the settings row DIRECTLY: Backpack Settings only mirrors settings into
        // config('settings.*') for web requests (it skips App::runningInConsole()), so
        // config() is empty inside a seeder. Querying the table works in any context and
        // reflects the latest value.
        $email = \Backpack\Settings\app\Models\Setting::where('key', 'email')->value('value')
            ?: config('settings.email');
        $emailHtml = $email
            ? '<a href="mailto:'.e($email).'">'.e($email).'</a>'
            : '⟦بريد الدعم⟧';
        $effectiveDate = now()->format('Y-m-d');

        $page->content_ar = str_replace(
            ['⟦تاريخ النشر⟧', '⟦بريد الدعم⟧'],
            [$effectiveDate, $emailHtml],
            $this->contentAr()
        );
        $page->content_en = str_replace(
            ['⟦publication date⟧', '⟦support email⟧'],
            [$effectiveDate, $email ? $emailHtml : '⟦support email⟧'],
            $this->contentEn()
        );
        $page->save();

        $emailNote = $email ? "support email: {$email}" : 'support email NOT set in settings — placeholder left in place';
        $this->command->info("Privacy Policy content updated for page #{$page->id} (slug: {$page->slug}). Effective date {$effectiveDate}, {$emailNote}.");
    }

    private function contentAr(): string
    {
        return <<<'HTML'
<p><strong>تاريخ السريان:</strong> ⟦تاريخ النشر⟧</p>

<p>في شركة عنوان الضيافة للاستثمار ("نحن") نحرص على خصوصية مستخدمي موقع dyafa.sa وتطبيقاتها على Android وiOS ("المنصة")، ونطبّق تدابير تقنية وتنظيمية مناسبة لحماية بياناتك الشخصية. توضح هذه السياسة كيف نجمع بياناتك ونستخدمها ونشاركها ونحميها، وفقاً لنظام حماية البيانات الشخصية في المملكة العربية السعودية ولوائحه التنفيذية.</p>

<h2>1. من نحن</h2>
<p>شركة عنوان الضيافة للاستثمار، شركة ذات مسؤولية محدودة، الرقم الوطني الموحد 7038416538. العنوان: مبنى 3385، شارع الثمامة، حي الندى، الرمز البريدي 13317، الرياض، المملكة العربية السعودية.</p>

<h2>2. المعلومات التي نجمعها</h2>
<p><strong>معلومات تقدمها أنت:</strong></p>
<ul>
  <li>الاسم الأول والأخير، البريد الإلكتروني، رقم الجوال، المسمى الوظيفي عند إنشاء الحساب.</li>
  <li>رقم الهوية الوطنية أو الإقامة أو الجواز.</li>
  <li>رقم جوال للطوارئ لشخص تختاره، وبتقديمه تؤكد أن صاحبه على علم بذلك.</li>
  <li>الصور التي ترفعها، من الكاميرا أو معرض الصور بعد موافقتك.</li>
  <li>الرسائل التي ترسلها عبر المحادثة المباشرة.</li>
</ul>
<p><strong>معلومات تُجمع أثناء استخدامك للمنصة:</strong></p>
<ul>
  <li>بيانات الحجز: الوحدة وتواريخ الوصول والمغادرة والمبالغ.</li>
  <li>بيانات الدفع: مرجع العملية والمبلغ والعملة. لا نحفظ رقم بطاقتك ولا رمز CVV، إذ تُعالَج لدى بوابة الدفع مباشرة.</li>
  <li>موقعك الجغرافي في التطبيق بعد موافقتك، لعرض الوحدات القريبة منك. يمكنك إلغاء الإذن من إعدادات جهازك في أي وقت.</li>
  <li>عنوان IP ونوع الجهاز والمتصفح، ورمز الإشعارات في التطبيق.</li>
</ul>
<p><strong>معلومات من جهات أخرى:</strong> إذا حجزت إحدى وحداتنا عبر Airbnb، نتلقى منها بيانات الحجز اللازمة لإدارة إقامتك.</p>

<h2>3. كيف نستخدم معلوماتك</h2>
<ul>
  <li>لإنشاء حسابك والتحقق منه برمز يُرسل إلى جوالك.</li>
  <li>لإتمام الحجوزات ومعالجة المدفوعات والمبالغ المستردة.</li>
  <li>لإدارة إقامتك، بما في ذلك إصدار رموز دخول الأقفال الذكية خلال مدة الحجز.</li>
  <li>لإرسال تأكيدات الحجز والإشعارات، والرد على استفساراتك.</li>
  <li>للتواصل مع جهة الطوارئ التي حددتها عند الحاجة.</li>
  <li>لأغراض التحقق من الهوية، وإدارة حسابك وخدمتك، والأغراض التشغيلية والإدارية للمنصة.</li>
  <li>لحماية المنصة من الاحتيال وإساءة الاستخدام، وللوفاء بالتزاماتنا النظامية والمحاسبية.</li>
</ul>

<h2>4. مشاركة البيانات مع أطراف ثالثة</h2>
<p>لا نبيع بياناتك الشخصية. نشارك الحد الأدنى اللازم مع مقدمي الخدمات التالين، الذين يعالجون البيانات لصالحنا ووفق تعليماتنا:</p>
<ul>
  <li>Geidea: معالجة المدفوعات.</li>
  <li>OwnerRez: مزامنة الحجوزات وتوفر الوحدات (بما فيها الحجوزات الواردة عبر Airbnb).</li>
  <li>TTLock / Sciener: إصدار رموز دخول الأقفال الذكية.</li>
  <li>تقنيات (Taqnyat): إرسال الرسائل النصية ورموز التحقق.</li>
  <li>Google: إرسال البريد الإلكتروني (Google Workspace)، وإشعارات التطبيق (Firebase)، وعرض الخرائط والبحث عن الأماكن.</li>
  <li>LiveChat: خدمة المحادثة المباشرة.</li>
  <li>Hostinger: استضافة المنصة وقاعدة البيانات.</li>
</ul>
<p>وقد نفصح عن بياناتك للجهات الحكومية المختصة متى طُلب ذلك نظاماً.</p>

<h2>5. نقل البيانات خارج المملكة</h2>
<p>يعالج بعض مقدمي الخدمات أعلاه البيانات على خوادم خارج المملكة، ومنهم Google وOwnerRez وAirbnb وLiveChat وTTLock وHostinger. نقتصر في ذلك على الحد الأدنى اللازم لتقديم الخدمة، ونلتزم بالضوابط النظامية لنقل البيانات خارج المملكة.</p>

<h2>6. ملفات تعريف الارتباط</h2>
<ul>
  <li>ملفات ضرورية: لتسجيل الدخول والحفاظ على جلستك، ولا تعمل المنصة بدونها.</li>
  <li>ملفات أطراف ثالثة: تضعها خدمة LiveChat وخرائط Google عند تحميل الصفحات التي تستخدمها.</li>
</ul>
<p>لا نستخدم أدوات تحليل أو ملفات إعلانية. ويمكنك إدارة ملفات تعريف الارتباط من إعدادات متصفحك.</p>

<h2>7. الاحتفاظ بالبيانات</h2>
<p>نحتفظ ببياناتك طوال فترة نشاط حسابك. وعند طلبك حذف الحساب، نحذف بياناتك خلال 30 يوماً، عدا سجلات الحجوزات والمدفوعات والفواتير التي نحتفظ بها للمدة التي تفرضها الأنظمة الضريبية والمحاسبية.</p>

<h2>8. حماية البيانات</h2>
<p>نطبق تدابير تقنية وتنظيمية لحماية بياناتك من الوصول غير المصرح به أو الفقدان أو سوء الاستخدام، منها تشفير الاتصال وتقييد وصول الموظفين حسب الحاجة.</p>

<h2>9. حقوقك</h2>
<p>يحق لك بموجب نظام حماية البيانات الشخصية:</p>
<ul>
  <li>العلم بكيفية جمع بياناتك ومعالجتها.</li>
  <li>الوصول إلى بياناتك والحصول على نسخة منها بصيغة مقروءة.</li>
  <li>طلب تصحيح بياناتك أو تحديثها.</li>
  <li>طلب حذف بياناتك، مع مراعاة الاستثناءات النظامية.</li>
  <li>سحب موافقتك في أي وقت.</li>
</ul>
<p>لممارسة هذه الحقوق تواصل معنا عبر بريد الدعم ⟦بريد الدعم⟧ أو من خلال صفحة «اتصل بنا» في المنصة، ونرد خلال 30 يوماً.</p>

<h2>10. الأطفال</h2>
<p>المنصة موجهة لمن بلغوا 18 عاماً فأكثر، ولا نجمع بيانات من هم دون ذلك عن علم.</p>

<h2>11. التغييرات على هذه السياسة</h2>
<p>قد نحدّث هذه السياسة من وقت لآخر. ننشر أي تحديث على هذه الصفحة مع تاريخ السريان الجديد، ونُشعرك بالتغييرات الجوهرية عبر البريد أو إشعار في المنصة.</p>

<h2>12. اتصل بنا</h2>
<p>لأي استفسار حول هذه السياسة أو لممارسة حقوقك:<br>
شركة عنوان الضيافة للاستثمار، الرياض، حي الندى، شارع الثمامة، مبنى 3385<br>
بريد الدعم: ⟦بريد الدعم⟧</p>
HTML;
    }

    private function contentEn(): string
    {
        return <<<'HTML'
<p><strong>Effective date:</strong> ⟦publication date⟧</p>

<p>At Unwan Al-Diyafa Investment Company ("we"), we care about the privacy of users of dyafa.sa and its Android and iOS apps (the "Platform"), and we apply appropriate technical and organizational measures to safeguard your personal data. This policy explains how we collect, use, share and protect your data, in accordance with the Personal Data Protection Law of the Kingdom of Saudi Arabia and its Implementing Regulations.</p>

<h2>1. Who we are</h2>
<p>Unwan Al-Diyafa Investment Company (شركة عنوان الضيافة للاستثمار), a limited liability company, Unified National Number 7038416538. Address: Building No. 3385, Al Thumamah Street, Al Nada District, Postal Code 13317, Riyadh, Saudi Arabia.</p>

<h2>2. Information we collect</h2>
<p><strong>Information you provide:</strong></p>
<ul>
  <li>First and last name, email address, mobile number, job title when you create an account.</li>
  <li>National ID, Iqama or passport number.</li>
  <li>An emergency contact number for a person you choose; by providing it, you confirm that person is aware.</li>
  <li>Photos you upload, from your camera or gallery with your permission.</li>
  <li>Messages you send through live chat.</li>
</ul>
<p><strong>Information collected when you use the Platform:</strong></p>
<ul>
  <li>Booking details: unit, check-in and check-out dates, amounts.</li>
  <li>Payment details: transaction reference, amount and currency. We do not store your card number or CVV; these are processed directly by the payment gateway.</li>
  <li>Your location in the app, with your permission, to show units near you. You can revoke this permission in your device settings at any time.</li>
  <li>IP address, device and browser type, and the app's notification token.</li>
</ul>
<p><strong>Information from others:</strong> if you book one of our units through Airbnb, we receive the booking details needed to manage your stay.</p>

<h2>3. How we use your information</h2>
<ul>
  <li>To create your account and verify it with a code sent to your mobile.</li>
  <li>To complete bookings and process payments and refunds.</li>
  <li>To manage your stay, including issuing smart lock access codes for your booking period.</li>
  <li>To send booking confirmations and notifications, and respond to your enquiries.</li>
  <li>To contact your emergency contact when needed.</li>
  <li>For identity verification, managing your account and service, and the Platform's operational and administrative purposes.</li>
  <li>To protect the Platform from fraud and misuse, and meet our legal and accounting obligations.</li>
</ul>

<h2>4. Sharing with third parties</h2>
<p>We do not sell your personal data. We share only what is necessary with the following service providers, who process data on our behalf and under our instructions:</p>
<ul>
  <li>Geidea: payment processing.</li>
  <li>OwnerRez: booking and availability synchronisation (including bookings received via Airbnb).</li>
  <li>TTLock / Sciener: smart lock access codes.</li>
  <li>Taqnyat: SMS and verification codes.</li>
  <li>Google: email delivery (Google Workspace), app notifications (Firebase), maps and place search.</li>
  <li>LiveChat: live chat support.</li>
  <li>Hostinger: hosting the Platform and its database.</li>
</ul>
<p>We may also disclose your data to competent government authorities where required by law.</p>

<h2>5. Transfers outside Saudi Arabia</h2>
<p>Some of the providers above process data on servers outside the Kingdom, including Google, OwnerRez, Airbnb, LiveChat, TTLock and Hostinger. We limit such transfers to what is necessary to provide the service and comply with the legal controls on transferring data outside the Kingdom.</p>

<h2>6. Cookies</h2>
<ul>
  <li>Essential cookies: for sign-in and keeping your session; the Platform does not work without them.</li>
  <li>Third-party cookies: set by LiveChat and Google Maps when pages using them load.</li>
</ul>
<p>We do not use analytics or advertising cookies. You can manage cookies in your browser settings.</p>

<h2>7. Data retention</h2>
<p>We keep your data while your account is active. When you ask us to delete your account, we delete your data within 30 days, except booking, payment and invoice records, which we keep for the period required by tax and accounting laws.</p>

<h2>8. Data security</h2>
<p>We apply technical and organisational measures to protect your data against unauthorised access, loss or misuse, including encrypted connections and need-based staff access.</p>

<h2>9. Your rights</h2>
<p>Under the Personal Data Protection Law, you have the right to:</p>
<ul>
  <li>Be informed of how your data is collected and processed.</li>
  <li>Access your data and obtain a copy in a readable format.</li>
  <li>Request correction or updating of your data.</li>
  <li>Request deletion of your data, subject to legal exceptions.</li>
  <li>Withdraw your consent at any time.</li>
</ul>
<p>To exercise these rights, contact us at our support email ⟦support email⟧ or via the "Contact Us" page on the Platform; we respond within 30 days.</p>

<h2>10. Children</h2>
<p>The Platform is intended for users aged 18 and over, and we do not knowingly collect data from anyone younger.</p>

<h2>11. Changes to this policy</h2>
<p>We may update this policy from time to time. We publish any update on this page with a new effective date, and notify you of material changes by email or an in-platform notice.</p>

<h2>12. Contact us</h2>
<p>For any question about this policy or to exercise your rights:<br>
Unwan Al-Diyafa Investment Company, Building No. 3385, Al Thumamah Street, Al Nada District, Riyadh<br>
Support email: ⟦support email⟧</p>
HTML;
    }
}
