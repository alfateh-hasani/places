<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BuildingRequest;
use App\Models\Building;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\LockCredentials;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

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
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

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
        CRUD::field('image')
            ->label('الصورة')
            ->type('image')
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
            $this->crud->addField([
                'name' => 'test_sciener_connection',
                'type' => 'custom_html',
                'value' => '
                    <div class="form-group col-md-12">
                        <form method="POST" action="'.route('admin.building.test-sciener-connection', $this->crud->getCurrentEntry()->id).'">
                            '.csrf_field().'
                            <button type="submit" class="btn btn-sm btn-outline-info">
                                <i class="la la-plug"></i> اختبار الاتصال بحساب TTLOCK
                            </button>
                        </form>
                    </div>',
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
        $this->crud->addField([
            'name' => 'check_in_time',
            'type' => 'time',
            'label' => __('cms.check_in_time'),
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

        return parent::update();
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
}
