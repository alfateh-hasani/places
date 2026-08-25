<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\Request;

/**
 * Class FeatureController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CustomerController extends CrudController
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
        CRUD::setModel(\App\Models\Customer::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/customer');
        $slider = __('cms.customer');
        $sliders = __('cms.customer');

        CRUD::setEntityNameStrings($slider, $sliders);

        if (! backpack_user()->can('customer.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update', 'delete']);

        // if (backpack_user()->can('customer.create')) {
        //     $this->crud->allowAccess('create');
        // }
        // if (backpack_user()->can('customer.update')) {
        //     $this->crud->allowAccess('update');
        // }
        // if (backpack_user()->can('customer.delete')) {
        //     $this->crud->allowAccess('delete');
        // }
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

        CRUD::addColumn([
            'name' => 'first_name',
            'type' => 'text',
            'label' => __('cms.first_name'),
        ]);

        CRUD::addColumn([
            'name' => 'last_name',
            'type' => 'text',
            'label' => __('cms.last_name'),
        ]);

        CRUD::addColumn([
            'name' => 'email',
            'type' => 'email',
            'label' => __('cms.email'),
        ]);

        CRUD::addColumn([
            'name' => 'phone',
            'type' => 'custom_html',
            'label' => __('cms.phone'),
            'value' => fn ($entry) => '<span dir="ltr">'.$entry->phone.'</span>',
            'searchLogic' => fn ($query, $column, $searchTerm) => $query->orWhere('phone', 'like', '%'.$searchTerm.'%'),
        ]);

        CRUD::addColumn([
            'name' => 'reviews_count',
            'type' => 'number',
            'label' => __('cms.reviews_count'),
            'wrapper' => [
                'element' => 'span',
                'class' => 'badge badge-success p-2',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'bookings_count',
            'type' => 'number',
            'label' => __('cms.bookings_count'),
            'wrapper' => [
                'element' => 'span',
                'class' => 'badge badge-primary p-2',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'emergency_phone',
            'type' => 'text',
            'label' => __('cms.emergency_phone'),
        ]);
        CRUD::addColumn([
            'name' => 'created_at',
            'type' => 'datetime',
            'label' => __('cms.created_at'),
        ]);

        CRUD::addColumn([
            'name' => 'blocked_at',
            'type' => 'custom_html',
            'label' => __('cms.customer_status'),
            'searchLogic' => false,
            // Inline style — theme-proof (badge-success/badge-danger depend on the active Backpack
            // theme's CSS actually defining those classes; inline color can't be lost to a theme gap).
            'value' => fn ($entry) => empty($entry->blocked_at)
                ? '<span style="background-color:#28a745;color:#fff;padding:.35em .65em;border-radius:6px;font-weight:bold;">'.__('cms.status_active').'</span>'
                : '<span style="background-color:#dc3545;color:#fff;padding:.35em .65em;border-radius:6px;font-weight:bold;">'.__('cms.status_blocked').'</span>',
        ]);

        CRUD::addColumn([
            'name' => 'block_reason',
            'type' => 'textarea',
            'label' => __('cms.block_reason'),
            'searchLogic' => false,
        ]);

        CRUD::addColumn([
            'name' => 'blocked_by',
            'type' => 'select',
            'label' => __('cms.blocked_by'),
            'entity' => 'blockedByUser',
            'attribute' => 'name',
            'model' => \App\Models\User::class,
            'searchLogic' => false,
        ]);

        CRUD::addButtonFromView('line', 'block_customer', 'block_customer', 'end');
        CRUD::addButtonFromView('line', 'unblock_customer', 'unblock_customer', 'end');

        Widget::add([
            'type' => 'view',
            'view' => 'admin.customers.block_modal',
        ])->to('before_content');

        // نفس نافذة التأكيد الأنيقة (SweetAlert) المستخدمة في شاشة طلبات تعديل التواريخ — بدل confirm() الافتراضي.
        Widget::add(['type' => 'view', 'view' => 'admin.date_changes.actions_script'])->to('before_content');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation() {}

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

    // showOperation

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::modifyColumn('phone', [
            'wrapper' => [
                'element' => 'span',
                'style' => 'direction: ltr; display: inline-block;',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'ownerrez_guest_id',
            'type' => 'text',
            'label' => 'OwnerRez Guest ID',
        ]);
    }

    /**
     * حظر العميل — يمنعه من تسجيل الدخول (API والويب)، بلا أي أثر على بياناته أو حجوزاته.
     * يقطع الوصول فوراً حتى لجلسة/توكن نشطين مسبقاً: توكنات Sanctum تُبطَل هنا مباشرة، والجلسة على
     * الويب يُنهيها EnsureCustomerNotBlocked في أول طلب لاحق للعميل (نفس تأثير الحظر لحظياً).
     */
    public function block($id, Request $request)
    {
        $customer = $this->authorizedCustomer($id);

        $validated = $request->validate([
            'block_reason' => 'required|string|max:1000',
        ], [], ['block_reason' => __('cms.block_reason')]);

        $customer->update([
            'blocked_at' => now(),
            'block_reason' => $validated['block_reason'],
            'blocked_by' => backpack_user()->id,
        ]);

        $customer->tokens()->delete();

        \Alert::success(__('cms.customer_blocked_successfully'))->flash();

        return back();
    }

    /**
     * رفع الحظر — يعيد للعميل القدرة على تسجيل الدخول.
     */
    public function unblock($id)
    {
        $customer = $this->authorizedCustomer($id);

        $customer->update([
            'blocked_at' => null,
            'block_reason' => null,
            'blocked_by' => null,
        ]);

        \Alert::success(__('cms.customer_unblocked_successfully'))->flash();

        return back();
    }

    private function authorizedCustomer($id): Customer
    {
        if (! backpack_user()->can('customer.list')) {
            abort(403, 'Unauthorized Access');
        }

        return Customer::findOrFail($id);
    }
}
