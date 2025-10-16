@php
    $activities = $widget['content']['activities'] ?? [];
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="la la-clock"></i> أحدث الأنشطة
        </h5>
    </div>
    <div class="card-body">
        @if(count($activities) > 0)
            <div class="timeline">
                @foreach($activities as $activity)
                    <div class="timeline-item">
                       
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h6 class="timeline-title">{{ $activity['title'] }}</h6>
                                <small class="text-muted">
                                     
                                    {{ \Carbon\Carbon::parse($activity['time'])->diffForHumans() }}
                                </small>
                            </div>
                            <div class="timeline-body">
                                <p class="mb-0">{{ $activity['description'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-4">
                <i class="la la-inbox la-3x text-muted"></i>
                <p class="text-muted mt-2">لا توجد أنشطة حديثة</p>
            </div>
        @endif
    </div>
     
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #dee2e6;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.timeline-title {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.timeline-body p {
    font-size: 13px;
    color: #6c757d;
    margin: 0;
}

.bg-primary { background-color: #007bff !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-success { background-color: #28a745 !important; }
.bg-info { background-color: #17a2b8 !important; }
</style>
