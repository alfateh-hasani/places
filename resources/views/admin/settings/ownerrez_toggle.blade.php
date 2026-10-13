@php($ownerrezEnabled = (bool) config('ownerrez.enabled', true))

<div class="card mb-3">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap" style="gap:.75rem;">
        <div>
            <h5 class="mb-1"><i class="la la-plug"></i> تكامل OwnerRez</h5>
            <div class="text-muted small mb-1">
                عند الإيقاف يتم تجاوز جميع عمليات OwnerRez بالكامل (الـ API، الويبهوك، المزامنة،
                المهام المجدولة، وواجهة الإدارة).
            </div>
            <div>
                الحالة الحالية:
                @if ($ownerrezEnabled)
                    <span class="badge badge-success">مفعّل</span>
                @else
                    <span class="badge badge-danger">معطّل</span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('admin.ownerrez.toggle') }}"
              onsubmit="return confirm(@js($ownerrezEnabled ? 'إيقاف تكامل OwnerRez؟' : 'تفعيل تكامل OwnerRez؟'));">
            @csrf
            <input type="hidden" name="enabled" value="{{ $ownerrezEnabled ? 0 : 1 }}">
            <button type="submit" class="btn {{ $ownerrezEnabled ? 'btn-danger' : 'btn-success' }}">
                <i class="la la-power-off"></i> {{ $ownerrezEnabled ? 'إيقاف التكامل' : 'تفعيل التكامل' }}
            </button>
        </form>
    </div>
</div>
