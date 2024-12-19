<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\LockRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
class LockController extends CrudController
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
        CRUD::setModel(\App\Models\Lock::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/lock');
        CRUD::setEntityNameStrings('lock', 'locks');

        if (!backpack_user()->can('lock.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);
        
        if (backpack_user()->can('lock.create')) {
            $this->crud->allowAccess('create');
        }
        if (backpack_user()->can('lock.update')) {
            $this->crud->allowAccess('update');
        }
        if (backpack_user()->can('lock.delete')) {
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
        CRUD::setFromDb(); // set columns from db columns.

        // Add Unlock button with modal trigger
        CRUD::addButtonFromView('line', 'unlock', 'unlock_modal_button', 'beginning');

        // Add Add Custom Passcode button with modal trigger
        CRUD::addButtonFromView('line', 'add_passcode', 'add_passcode_modal_button', 'beginning');

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(LockRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
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

    public function unlock($id){
    $lock = \App\Models\Lock::find($id);

    \Alert::error('This is a red bubble.')->flash();


    if ($lock) {
        // Call your service to unlock the lock
        $service = app(\App\Services\ScienerLockService::class);
        $response = $service->unlock($lock->lock_id);


        if ($response['success']) {

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://whatsapp.madar-solutions.com/api/create-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
            'appkey' => '6a8aebd3-a6c5-4527-9bf7-aa2d948a366a',
            'authkey' => 'P41VtHjDYwEhTm5FmkRWZ0wnK5dwpFUyGpl7z2DYGjo6HT81dk',
            'to' => '+201019699363',
            'message' => 'تم فتح القفل رقم '.$id,
            'sandbox' => 'false'
            ),
            ));

            $response2 = curl_exec($curl);

            curl_close($curl);
            dd($response);
            return redirect()->back()->with('success', 'Lock unlocked successfully.');
        }
        dd($response);

    }

    return redirect()->back()->with('error', 'Lock not found.');
}

public function addPasscode(Request $request , $id)
{


    $lock = \App\Models\Lock::find($id);

    if ($lock) {
        $keyboardPwd = $request->input('keyboardPwd');
        // Return a view with a form to add a custom passcode
        $service = app(\App\Services\ScienerLockService::class);
        $response = $service->addCustomPasscode($lock->lock_id , $keyboardPwd);

        if ($response['success']) {
        $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://whatsapp.madar-solutions.com/api/create-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
            'appkey' => '6a8aebd3-a6c5-4527-9bf7-aa2d948a366a',
            'authkey' => 'P41VtHjDYwEhTm5FmkRWZ0wnK5dwpFUyGpl7z2DYGjo6HT81dk',
            'to' => '+201019699363',
            'message' => 'رقم الباس كود هو:  '.$keyboardPwd,
            'sandbox' => 'false'
            ),
            ));

            $response2 = curl_exec($curl);

            curl_close($curl);
            }
            dd($response);


    }

    return redirect()->back()->with('error', 'Lock not found.');
}


}
