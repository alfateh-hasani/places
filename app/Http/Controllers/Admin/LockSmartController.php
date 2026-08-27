<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SmartLockRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
class LockSmartController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\SmartLock::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/smart-lock');
        $singular = __('cms.smart_lock');
        $plural = __('cms.smart_locks');
        CRUD::setEntityNameStrings($singular, $plural);

        if (!backpack_user()->can('smartlock.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('smartlock.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('smartlock.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('smartlock.delete')) {
            $this->crud->allowAccess('delete');
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'lock_id',
            'label' =>  __('cms.lock_id'),
        ]);
        CRUD::addColumn([
            'name' => 'lock_name',
            'label' => __('cms.lock_name'),
        ]);
        
        CRUD::addColumn([
            'name' => 'building_id',
            'label' => __('cms.building'),
            'type' => 'select',
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => \App\Models\Building::class,
        ]);


         // إضافة فلتر حسب المشروع
         CRUD::filter('select2')
         ->type('select2')
         ->label('المشروع')
         ->values(function() {
             return \App\Models\Building::all()->pluck('name_ar', 'id')->toArray();
         })
         ->whenActive(function($value) {
             $this->crud->addClause('where', 'building_id', $value);
         });
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SmartLockRequest::class);
        $this->crud->addField([
            'name' => 'lock_id',
            'label' => __('cms.lock_id'),
            'type' => 'text',
            'wrapper' => ['class' => 'col-md-6'],
            'attributes' => [
                'pattern' => '[a-zA-Z0-9]+', 
                'title' => 'Only English letters are allowed',
            ],
        ]);
        
        $this->crud->addField([
            'name' => 'lock_name',
            'label' =>  __('cms.lock_name'),
            'type' => 'text',
            'wrapper' => ['class' => 'col-md-6'],
        ]);
     
        $this->crud->addField([
            'name' => 'building_id',
            'type' => 'select',
            'label' => __('cms.building'),
            'entity' => 'building',
            'attribute' => 'name_ar',
            'model' => \App\Models\Building::class,
            'wrapper' => ['class' => 'col-md-6'],
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

     protected function setupShowOperation(){
        $this->setupListOperation();

     }


     //getSmartLocks
     public function getSmartLocks(Request $request)
    {
   

        $building_id = $request->input('building_id');

        
        
        

        // جلب الأقفال الذكية بناءً على building_id
        $smartLocks = \App\Models\SmartLock::query();

        if($building_id){
            $smartLocks =   $smartLocks->where('building_id', $building_id);
        }

        $smartLocks = $smartLocks->get();
        
        // تنسيق البيانات لـ select2
        $formattedSmartLocks = $smartLocks->map(function ($smartLock) {
            return [
                'id' => $smartLock->id,
                'text' => $smartLock->full_name, // استخدم الحقل الذي تريد عرضه
            ];
        });
        
        // إرجاع البيانات كـ JSON
        return response()->json($formattedSmartLocks);
    }

    public function getLocks(Request $request)
    {
        $buildingId = $request->input('building_id');

        if (!$buildingId) {
            return response()->json([]);
        }

        $locks = \App\Models\SmartLock::where('building_id', $buildingId)
            ->select('id', 'lock_id')
            ->get();

        return response()->json($locks);
    }


}
