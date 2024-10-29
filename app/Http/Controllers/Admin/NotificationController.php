<?php
namespace App\Http\Controllers\Admin;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class NotificationController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    public function setup()
    {
        CRUD::setModel(\App\Models\Notification::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/notifications');
        CRUD::setEntityNameStrings('notification', 'notifications');
    }

    protected function setupListOperation()
    {
        // Define columns for the List view in the admin panel
        CRUD::column('id');
        CRUD::column('type')->type('text');
        CRUD::column('title_ar')->label('Title Arabic');
        CRUD::column('title_en')->label('Title English');
        CRUD::column('description_ar')->type('textarea')->label('Description Arabic');
        CRUD::column('description_en')->type('textarea')->label('Description English');
        CRUD::column('image')->type('image');
        CRUD::column('process_type')->type('text');
        CRUD::column('process_status')->type('text');
        CRUD::column('created_at');
        CRUD::column('updated_at');
    }

    protected function setupCreateOperation()
    {
        // Define validation rules for creating a new notification
        CRUD::setValidation([
            'type' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'image' => 'nullable',
            'process_type' => 'required|string|max:255',
            'process_status' => 'required|string|max:255',
        ]);

        // Define fields for the Create and Edit forms
        CRUD::field('type')->type('hidden')->value('all');
         
        CRUD::field('title_ar')->type('text')->label(__('cms.title_ar'))->attributes(['required' => 'required'])->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('title_en')->type('text')->label(__('cms.title_en'))->attributes(['required' => 'required'])->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('description_ar')->type('textarea')->label(__('cms.description_ar'))->attributes(['required' => 'required'])->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('description_en')->type('textarea')->label(__('cms.description_en'))->attributes(['required' => 'required'])->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('image')->type('image')->upload(true);
        CRUD::field('process_type')->type('hidden')->value('notification');
        CRUD::field('process_status')->type('hidden')->value('unread');
    }

    protected function setupUpdateOperation()
    {
        // Reuse the setup from Create Operation for consistency
        $this->setupCreateOperation();
    }
}
