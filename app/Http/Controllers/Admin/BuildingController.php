<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BuildingRequest;
use App\Models\Building;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\LockCredentials;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/**
 * Class BuildingController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BuildingController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Building::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/building');
        CRUD::setEntityNameStrings('مبنى', 'البناء');

        if (! backpack_user()->can('building.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);

        if (backpack_user()->can('building.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('building.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('building.delete')) {
            $this->crud->allowAccess('delete');
        }
        if (backpack_user()->hasRole('supervisor')) {
            $this->crud->addClause('where', 'supervisor_id', backpack_user()->id);
        }

    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {

        $this->crud->addColumn([
            'name' => 'name_ar',
            'type' => 'text',
            'label' => 'الاسم بالعربي',
        ]);
        $this->crud->addColumn([
            'name' => 'name_en',
            'type' => 'text',
            'label' => 'الاسم بالانجليزي',
        ]);
        $this->crud->addColumn([
            'name' => 'image',
            'type' => 'image',
            'label' => 'الصورة',
        ]);
        $this->crud->addColumn([
            'name' => 'city_id',
            'type' => 'select',
            'label' => 'المدينة',
            'entity' => 'city',
            'attribute' => 'name_ar',
            'model' => \App\Models\City::class,
        ]);

        // supervisor_id
        $this->crud->addColumn([
            'name' => 'supervisor_id',
            'type' => 'select',
            'label' => 'المشرف',
            'entity' => 'supervisor',
            'attribute' => 'name',
            'model' => \App\Models\User::class,
        ]);
        // check_out_time check_in_time
        $this->crud->addColumn([
            'name' => 'check_in_time',
            'type' => 'time',
            'label' => __('cms.check_in_time'),
        ]);

        $this->crud->addColumn([
            'name' => 'check_out_time',
            'type' => 'time',
            'label' => __('cms.check_out_time'),
        ]);

        // Inline activation switch — flips buildings.is_active over AJAX straight from
        // the list. Only rendered for users who can update; everyone else sees a
        // read-only yes/no so they can't toggle without permission.
        if ($this->crud->hasAccess('update')) {
            $this->crud->addColumn([
                'name' => 'is_active',
                'label' => __('cms.is_active'),
                'type' => 'view',
                'view' => 'admin.columns.active_switch',
            ]);

            Widget::add([
                'type' => 'view',
                'view' => 'admin.building.active_switch_script',
            ])->to('after_content');
        } else {
            $this->crud->addColumn([
                'name' => 'is_active',
                'type' => 'boolean',
                'label' => __('cms.is_active'),
            ]);
        }
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(BuildingRequest::class);
        // add image
        //
        // Building::getImageAttribute() (used site-wide for the public-facing photo URL) shares
        // the 'image' name with this field, so Backpack's default value lookup ($entry->image)
        // resolves through that accessor and hands the cropper field an already-absolute S3 URL.
        // The field template then prefixes its own disk base URL onto it, doubling the domain.
        // Supplying the relative media path explicitly bypasses the accessor and avoids that.
        CRUD::field('image')
            ->label('الصورة')
            ->type('image')
            ->value($this->currentImageFieldValue())
            ->withMedia([
                'collection' => 'image', // will pick the collection definition from your model
            ]);

        $this->crud->addField([
            'name' => 'name_ar',
            'type' => 'text',
            'label' => __('cms.name_ar'),
            'attributes' => [
                'placeholder' => __('cms.name_ar'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'name_en',
            'type' => 'text',
            'label' => __('cms.name_en'),
            'attributes' => [
                'placeholder' => __('cms.name_en'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'ttlock_username',
            'type' => 'text',
            'label' => 'TTLOCK Username',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'ttlock_password',
            'type' => 'password',
            'label' => 'TTLOCK Password',
            'value' => '',
            'hint' => $this->crud->getCurrentEntry() ? 'اتركه فارغاً للإبقاء على كلمة المرور الحالية' : null,
            'attributes' => [
                'autocomplete' => 'new-password',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        if ($this->crud->getCurrentEntry()) {
            // NOTE: must NOT nest a <form> here — this custom_html renders inside Backpack's
            // main edit <form>, and a nested form makes the browser close the outer form early,
            // orphaning every field (and the Save button) that follows. Instead, submit a
            // detached form built in JS on click, so the TTLOCK test stays a real POST.
            $this->crud->addField([
                'name' => 'test_sciener_connection',
                'type' => 'custom_html',
                'value' => '
                    <div class="form-group col-md-12">
                        <button type="button" class="btn btn-sm btn-outline-info"
                                data-test-sciener
                                data-action="'.route('admin.building.test-sciener-connection', $this->crud->getCurrentEntry()->id).'"
                                data-token="'.csrf_token().'">
                            <i class="la la-plug"></i> اختبار الاتصال بحساب TTLOCK
                        </button>
                    </div>
                    <script>
                        document.querySelectorAll("[data-test-sciener]").forEach(function (btn) {
                            btn.addEventListener("click", function () {
                                var form = document.createElement("form");
                                form.method = "POST";
                                form.action = btn.dataset.action;
                                var token = document.createElement("input");
                                token.type = "hidden";
                                token.name = "_token";
                                token.value = btn.dataset.token;
                                form.appendChild(token);
                                document.body.appendChild(form);
                                form.submit();
                            });
                        });
                    </script>',
            ]);
        }

        $this->crud->addField([
            'name' => 'address_ar',
            'type' => 'text',
            'label' => __('cms.address_ar'),
            'attributes' => [
                'placeholder' => __('cms.address_ar'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'address_en',
            'type' => 'text',
            'label' => __('cms.address_en'),
            'attributes' => [
                'placeholder' => __('cms.address_en'),
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'city_id',
            'type' => 'select2',
            'label' => __('cms.city'),
            'entity' => 'city',
            'attribute' => 'name_ar',
            'model' => \App\Models\City::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.city_select2'),
        ]);

        $this->crud->addField([
            'name' => 'supervisor_id',
            'type' => 'select2',
            'label' => __('cms.supervisor'),
            'entity' => 'supervisor',
            'attribute' => 'name',
            'model' => \App\Models\User::class,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => __('cms.supervisor_select2'),
        ]);

        // map_link
        $this->crud->addField([
            'name' => 'latitude',
            'type' => 'text',
            'label' => __('cms.latitude'),

            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'longitude',
            'type' => 'text',
            'label' => __('cms.longitude'),

            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        // add check_in_time check_out_time
        // `check_*_time` is cast to `datetime` on the model, which stringifies as "Y-m-d H:i:s" —
        // an <input type="time"> silently rejects that and leaves itself empty, which then fails
        // native `required` validation with no visible error. Format it to "H:i" explicitly.
        $this->crud->addField([
            'name' => 'check_in_time',
            'type' => 'time',
            'label' => __('cms.check_in_time'),
            'value' => $this->crud->getCurrentEntry()?->check_in_time?->format('H:i'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'check_out_time',
            'type' => 'time',
            'label' => __('cms.check_out_time'),
            'value' => $this->crud->getCurrentEntry()?->check_out_time?->format('H:i'),
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'map',
            'type' => 'textarea',
            'label' => 'كود تضمين الخريطة من جوجل وليس الرابط',
            'attributes' => [
                'required' => 'required',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'slug',
            'type' => 'text',
            'label' => 'رابط الصفحة',
            'attributes' => [
                'placeholder' => 'slug',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'seo_title_ar',
            'type' => 'text',
            'label' => __('cms.seo_title_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'seo_title_en',
            'type' => 'text',
            'label' => __('cms.seo_title_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'seo_description_ar',
            'type' => 'text',
            'label' => __('cms.seo_description_ar'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
        $this->crud->addField([
            'name' => 'seo_description_en',
            'type' => 'text',
            'label' => __('cms.seo_description_en'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'link',
            'type' => 'text',
            'label' => 'رابط خرائط جوجل',
            'attributes' => [

                'placeholder' => 'link',
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'sort_order',
            'type' => 'number',
            'label' => __('cms.sort_order'),
            'attributes' => [
                'min' => 0,
                'max' => 10000,
            ],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        $this->crud->addField([
            'name' => 'is_active',
            'type' => 'select_from_array',
            'label' => __('cms.is_active'),
            'options' => [1 => __('cms.yes'), 0 => __('cms.no')],
            'wrapperAttributes' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

    }

    /**
     * The 'image' field's relative media path (e.g. `public/buildings/13/images/2467/file.jpg`),
     * computed the same way the media library's own uploader does it. See the comment above the
     * 'image' field definition for why this needs to be supplied explicitly.
     */
    private function currentImageFieldValue(): ?string
    {
        $media = $this->crud->getCurrentEntry()?->getFirstMedia('image');

        if (! $media) {
            return null;
        }

        return PathGeneratorFactory::create($media)->getPath($media).$media->file_name;
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::addColumn([
            'name' => 'address_ar',
            'type' => 'textarea',
            'label' => __('cms.address_ar'),
            'max' => 19100,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        CRUD::addColumn([
            'name' => 'address_en',
            'type' => 'textarea',
            'label' => __('cms.address_en'),
            'max' => 19100,
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
        // link
        CRUD::addColumn([
            'name' => 'link',
            'type' => 'text',
            'label' => __('cms.map_link'),
            'wrapperAttributes' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

    }

    /**
     * لا نستبدل كلمة مرور TTLOCK المحفوظة إن تُرك الحقل فارغاً عند التعديل.
     */
    public function update()
    {
        if (empty($this->crud->getRequest()->input('ttlock_password'))) {
            $this->crud->getRequest()->request->remove('ttlock_password');
        }

        // `update()` is provided by the UpdateOperation trait (aliased to traitUpdate above),
        // not by the parent CrudController — so parent::update() would not resolve.
        return $this->traitUpdate();
    }

    /**
     * اختبار حيّ لاتصال حساب TTLOCK/Sciener الخاص بهذا المبنى.
     */
    public function testScienerConnection($id, LockProviderInterface $provider)
    {
        if (! backpack_user()->can('building.update')) {
            abort(403, 'Unauthorized Access');
        }

        $building = Building::findOrFail($id);

        if (! $building->ttlock_username || ! $building->ttlock_password) {
            \Alert::error('لم يتم إدخال بيانات TTLOCK لهذا المبنى بعد.')->flash();

            return back();
        }

        $result = $provider->testConnection(new LockCredentials(
            lockId: '',
            username: $building->ttlock_username,
            password: $building->ttlock_password,
        ));

        if ($result->ok) {
            \Alert::success('نجح الاتصال بحساب Sciener لهذا المبنى.')->flash();
        } else {
            \Alert::error("فشل الاتصال بحساب Sciener: [{$result->vendorErrorCode}] {$result->message}")->flash();
        }

        return back();
    }

    /**
     * Flip a building's active flag from the list's inline switch (AJAX).
     * An inactive building hides all of its units from the public site & API.
     */
    public function toggleActive(int $id): \Illuminate\Http\JsonResponse
    {
        if (! backpack_user()->can('building.update')) {
            abort(403, 'Unauthorized Access');
        }

        $building = Building::findOrFail($id);
        $building->is_active = ! $building->is_active;
        $building->save();

        return response()->json([
            'is_active' => $building->is_active,
            'message' => $building->is_active
                ? __('cms.building_activated')
                : __('cms.building_deactivated'),
        ]);
    }
}
