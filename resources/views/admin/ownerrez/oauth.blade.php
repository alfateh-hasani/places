@extends(backpack_view('blank'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="la la-link"></i> ربط OwnerRez OAuth
                    </h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="la la-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="la la-exclamation-triangle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(!$isConfigured)
                        <div class="alert alert-warning">
                            <h5><i class="la la-exclamation-triangle"></i> OAuth غير مُعد</h5>
                            <p class="mb-3">يجب إضافة بيانات OAuth App إلى ملف <code>.env</code></p>
                            
                            <h6 class="mt-3">الخطوات:</h6>
                            <ol class="mb-3">
                                <li>اذهب إلى <a href="https://secure.ownerrez.com" target="_blank">OwnerRez</a></li>
                                <li>Settings → API & Integrations → OAuth Apps</li>
                                <li>انقر "Create OAuth App"</li>
                                <li>املأ البيانات:
                                    <ul>
                                        <li><strong>App Name:</strong> Dyafa Integration</li>
                                        <li><strong>Redirect URI:</strong> <code>{{ config('ownerrez.oauth.redirect_uri') }}</code></li>
                                        <li><strong>Scopes:</strong> all</li>
                                        <li><strong>Webhook URL:</strong> <code>{{ url('/api/ownerrez') }}</code></li>
                                    </ul>
                                </li>
                                <li>انسخ Client ID و Client Secret</li>
                                <li>أضفهما إلى <code>.env</code>:
                                    <pre class="bg-dark text-white p-3 rounded mt-2">OWNERREZ_OAUTH_CLIENT_ID=c_your_client_id
OWNERREZ_OAUTH_CLIENT_SECRET=s_your_client_secret</pre>
                                </li>
                            </ol>
                        </div>
                    @elseif($isConnected)
                        <div class="alert alert-success">
                            <h5><i class="la la-check-circle"></i> متصل بنجاح!</h5>
                            <p class="mb-0">تم ربط حسابك في OwnerRez بنجاح</p>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">معرف المستخدم</h6>
                                        <h4 class="mb-0">{{ $userId }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-2">Access Token</h6>
                                        <h4 class="mb-0">
                                            <code>{{ substr($accessToken, 0, 15) }}...</code>
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <form action="{{ route('admin.ownerrez.oauth.disconnect') }}" method="POST" 
                                  onsubmit="return confirm('هل أنت متأكد من قطع الاتصال؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="la la-unlink"></i> قطع الاتصال
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="la la-link" style="font-size: 80px; color: #667eea;"></i>
                            <h4 class="mt-3 mb-4">ربط حسابك في OwnerRez</h4>
                            <p class="text-muted mb-4">
                                انقر على الزر أدناه للربط مع OwnerRez<br>
                                سيتم تحويلك إلى صفحة التفويض
                            </p>
                            
                            <a href="{{ route('admin.ownerrez.oauth.authorize') }}" 
                               class="btn btn-primary btn-lg">
                                <i class="la la-external-link"></i> ربط مع OwnerRez
                            </a>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h6><i class="la la-info-circle"></i> ماذا سيحدث؟</h6>
                            <ol class="mb-0 pl-3">
                                <li>سيتم تحويلك إلى صفحة OwnerRez</li>
                                <li>قم بتسجيل الدخول إلى حسابك</li>
                                <li>اضغط "Authorize" للسماح بالوصول</li>
                                <li>سيتم تحويلك تلقائياً إلى صفحة النجاح</li>
                                <li>سيتم حفظ Access Token تلقائياً</li>
                            </ol>
                        </div>
                    @endif
                </div>
            </div>

            @if($isConfigured)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="la la-cog"></i> إعدادات OAuth
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th width="200">Client ID</th>
                                <td><code>{{ config('ownerrez.oauth.client_id') }}</code></td>
                            </tr>
                            <tr>
                                <th>Client Secret</th>
                                <td><code>{{ substr(config('ownerrez.oauth.client_secret'), 0, 10) }}...</code></td>
                            </tr>
                            <tr>
                                <th>Redirect URI</th>
                                <td><code>{{ config('ownerrez.oauth.redirect_uri') }}</code></td>
                            </tr>
                            <tr>
                                <th>Webhook URL</th>
                                <td><code>{{ url('/api/ownerrez') }}</code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('after_styles')
<style>
    .card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }
    pre {
        font-size: 12px;
    }
</style>
@endsection

