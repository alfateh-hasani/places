<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class BookingChannelConflictController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\BookingChannelConflict::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/booking-channel-conflicts');
        CRUD::setEntityNameStrings('تعارض قناة حجز', 'تعارضات قنوات الحجز');

        if (!backpack_user()->can('booking_channel_conflict.list')) {
            abort(403, 'Unauthorized Access - List');
        }

        $this->crud->denyAccess(['create', 'update']);
        
        if (backpack_user()->can('booking_channel_conflict.delete')) {
            $this->crud->allowAccess('delete');
        }
    }

    protected function setupListOperation()
    {
        // إضافة أعمدة العرض
        CRUD::column('id')
            ->label('ID')
            ->type('text');

        CRUD::column('apartment_id')
            ->label('العقار')
            ->type('closure')
            ->function(function($entry) {
                return $entry->apartment_name;
            });

        CRUD::column('channel')
            ->label('القناة')
            ->type('closure')
            ->function(function($entry) {
                return $entry->channel_label;
            });

        CRUD::column('external_uid')
            ->label('معرف خارجي')
            ->type('text');

        CRUD::column('ext_check_in')
            ->label('تاريخ الوصول')
            ->type('date');

        CRUD::column('ext_check_out')
            ->label('تاريخ المغادرة')
            ->type('date');

        CRUD::column('duration')
            ->label('المدة (أيام)')
            ->type('closure')
            ->function(function($entry) {
                return $entry->duration . ' يوم';
            });

        CRUD::column('conflicting_booking_id')
            ->label('رقم الحجز المتعارض')
            ->type('closure')
            ->function(function($entry) {
                return $entry->booking_number;
            });

        CRUD::column('conflict_status')
            ->label('حالة التعارض')
            ->type('closure')
            ->function(function($entry) {
                return $entry->conflict_status_badge;
            });

        CRUD::column('created_at')
            ->label('تاريخ الإنشاء')
            ->type('datetime');

        // إضافة فلاتر
        CRUD::addFilter([
            'name' => 'channel',
            'type' => 'dropdown',
            'label' => 'القناة'
        ], [
            'airbnb' => 'Airbnb',
            'booking' => 'Booking.com',
            'expedia' => 'Expedia',
        ], function($value) {
            CRUD::addClause('where', 'channel', $value);
        });

        CRUD::addFilter([
            'name' => 'conflict_status',
            'type' => 'dropdown',
            'label' => 'حالة التعارض'
        ], [
            'active' => 'نشط',
            'resolved' => 'محلول',
        ], function($value) {
            if ($value === 'active') {
                CRUD::addClause('whereNull', 'conflicting_booking_id');
            } else {
                CRUD::addClause('whereNotNull', 'conflicting_booking_id');
            }
        });

        CRUD::addFilter([
            'name' => 'apartment_id',
            'type' => 'select2',
            'label' => 'العقار'
        ], function() {
            return \App\Models\Apartment::all()->pluck('name_ar', 'id')->toArray();
        }, function($value) {
            CRUD::addClause('where', 'apartment_id', $value);
        });

        // إضافة أزرار إضافية
        CRUD::addButtonFromView('top', 'resolve_conflicts', 'resolve_conflicts', 'end');
        CRUD::addButtonFromView('line', 'resolve_conflict', 'resolve_conflict', 'end');
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        // إضافة تفاصيل إضافية في صفحة العرض
        CRUD::column('context')
            ->label('السياق')
            ->type('closure')
            ->function(function($entry) {
                if ($entry->context) {
                    return '<pre>' . json_encode($entry->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                }
                return 'لا توجد بيانات سياق';
            });
    }

    public function resolveConflict($id)
    {
        $conflict = \App\Models\BookingChannelConflict::findOrFail($id);
        
        if ($conflict->conflicting_booking_id) {
            \Alert::error('هذا التعارض محلول بالفعل')->flash();
            return redirect()->back();
        }

        // يمكن إضافة منطق لحل التعارض هنا
        \Alert::info('سيتم إضافة منطق حل التعارض قريباً')->flash();
        return redirect()->back();
    }

    public function resolveAllConflicts()
    {
        // يمكن إضافة منطق لحل جميع التعارضات هنا
        \Alert::info('سيتم إضافة منطق حل جميع التعارضات قريباً')->flash();
        return redirect()->back();
    }
}
