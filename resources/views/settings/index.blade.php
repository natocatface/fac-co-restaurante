@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0"><i class="bi bi-gear-fill me-2"></i>Configuración</h2>
                    <p class="text-muted mb-0">Datos del restaurante y Facturación Electrónica DIAN (Colombia)</p>
                </div>
            </div>

            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <h5 class="fw-bold text-primary mb-3">Datos del Restaurante</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre del Restaurante</label>
                                <input type="text" name="company_name" class="form-control" value="{{ $settings['company_name'] ?? '' }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Teléfono</label>
                                <input type="text" name="company_phone" class="form-control" value="{{ $settings['company_phone'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Dirección</label>
                                <input type="text" name="company_address" class="form-control" value="{{ $settings['company_address'] ?? '' }}">
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        <h5 class="fw-bold text-primary mb-3">Región y Sistema</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-clock"></i> Zona Horaria</label>
                                <select name="timezone" class="form-select bg-light border-primary">
                                    @foreach($timezones as $tz => $label)
                                        <option value="{{ $tz }}" {{ ($settings['timezone'] ?? 'America/Bogota') == $tz ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hora del sistema: <strong>{{ \Carbon\Carbon::now()->format('H:i:s') }}</strong></small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Moneda</label>
                                <select name="currency_symbol" class="form-select">
                                    <option value="$" {{ ($settings['currency_symbol'] ?? '$') == '$' ? 'selected' : '' }}>$ (COP / USD)</option>
                                    <option value="COP" {{ ($settings['currency_symbol'] ?? '') == 'COP' ? 'selected' : '' }}>COP</option>
                                    <option value="€" {{ ($settings['currency_symbol'] ?? '') == '€' ? 'selected' : '' }}>€</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Mensaje Pie de Ticket</label>
                                <input type="text" name="ticket_footer" class="form-control" value="{{ $settings['ticket_footer'] ?? '¡Gracias por su visita!' }}">
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        <h5 class="fw-bold text-primary mb-3">Logotipo</h5>
                        <div class="row align-items-center mb-4">
                            <div class="col-md-8">
                                <label class="form-label">Subir Logo (Ticket y Sistema)</label>
                                <input type="file" name="company_logo" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-4 text-center">
                                @if(isset($settings['company_logo']) && $settings['company_logo'])
                                    <img src="{{ asset('storage/'.$settings['company_logo']) }}" class="img-thumbnail" style="max-height: 80px;">
                                @else
                                    <div class="p-3 border rounded bg-light text-muted"><i class="bi bi-image fs-1"></i></div>
                                @endif
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        {{-- ═══════════════════════════════════════════════════════════════ --}}
                        {{--    FACTURACIÓN ELECTRÓNICA — DIAN (COLOMBIA)                   --}}
                        {{-- ═══════════════════════════════════════════════════════════════ --}}
                        <h5 class="fw-bold text-danger mb-3">
                            <i class="bi bi-receipt-cutoff me-2"></i>Facturación Electrónica · DIAN Colombia
                        </h5>

                        @php
                            $env = (string) ($settings['dian_environment'] ?? '2');
                            $tieneCert = !empty($settings['dian_cert_path']);
                        @endphp

                        <div class="alert {{ $env === '1' ? 'alert-danger' : 'alert-warning' }} py-2 mb-3">
                            <strong>Ambiente actual:</strong>
                            {{ $env === '1' ? 'PRODUCCIÓN (emisión real ante la DIAN)' : 'HABILITACIÓN (pruebas DIAN)' }}
                            @if(!$tieneCert)
                                · <span class="badge bg-secondary">Sin certificado digital cargado</span>
                            @endif
                        </div>

                        <h6 class="fw-bold mt-3 mb-2 text-secondary">Datos del Emisor (Empresa)</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">NIT <span class="text-danger">*</span></label>
                                <input type="text" name="dian_company_nit" class="form-control"
                                       value="{{ $settings['dian_company_nit'] ?? '' }}" placeholder="900123456">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">DV</label>
                                <input type="text" name="dian_company_dv" class="form-control"
                                       value="{{ $settings['dian_company_dv'] ?? '' }}" maxlength="2" placeholder="7">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Razón Social <span class="text-danger">*</span></label>
                                <input type="text" name="dian_company_razon_social" class="form-control"
                                       value="{{ $settings['dian_company_razon_social'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Nombre Comercial</label>
                                <input type="text" name="dian_company_nombre_comercial" class="form-control"
                                       value="{{ $settings['dian_company_nombre_comercial'] ?? '' }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tipo persona</label>
                                <select name="dian_company_tipo_persona" class="form-select">
                                    <option value="1" {{ ($settings['dian_company_tipo_persona'] ?? '1') == '1' ? 'selected' : '' }}>Jurídica</option>
                                    <option value="2" {{ ($settings['dian_company_tipo_persona'] ?? '') == '2' ? 'selected' : '' }}>Natural</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Régimen</label>
                                <select name="dian_company_regimen" class="form-select">
                                    <option value="48" {{ ($settings['dian_company_regimen'] ?? '') == '48' ? 'selected' : '' }}>48 — Responsable de IVA</option>
                                    <option value="49" {{ ($settings['dian_company_regimen'] ?? '49') == '49' ? 'selected' : '' }}>49 — No responsable de IVA</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Responsabilidad fiscal</label>
                                <input type="text" name="dian_company_responsabilidad" class="form-control"
                                       value="{{ $settings['dian_company_responsabilidad'] ?? 'R-99-PN' }}" placeholder="R-99-PN / O-13">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Actividad económica (CIIU)</label>
                                <input type="text" name="dian_company_actividad_economica" class="form-control"
                                       value="{{ $settings['dian_company_actividad_economica'] ?? '5611' }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dirección fiscal</label>
                                <input type="text" name="dian_company_address" class="form-control"
                                       value="{{ $settings['dian_company_address'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Cód. Ciudad</label>
                                <input type="text" name="dian_company_city_code" class="form-control"
                                       value="{{ $settings['dian_company_city_code'] ?? '11001' }}" placeholder="11001">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Cód. Depto.</label>
                                <input type="text" name="dian_company_dept_code" class="form-control"
                                       value="{{ $settings['dian_company_dept_code'] ?? '11' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Municipio</label>
                                <input type="text" name="dian_company_municipio_nombre" class="form-control"
                                       value="{{ $settings['dian_company_municipio_nombre'] ?? 'BOGOTA' }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Teléfono</label>
                                <input type="text" name="dian_company_phone" class="form-control"
                                       value="{{ $settings['dian_company_phone'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="dian_company_email" class="form-control"
                                       value="{{ $settings['dian_company_email'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">País</label>
                                <input type="text" name="dian_company_country_code" class="form-control"
                                       value="{{ $settings['dian_company_country_code'] ?? 'CO' }}" maxlength="2">
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-2 text-secondary">Ambiente y Software DIAN</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Ambiente <span class="text-danger">*</span></label>
                                <select name="dian_environment" class="form-select fw-bold">
                                    <option value="2" {{ $env === '2' ? 'selected' : '' }}>HABILITACIÓN (pruebas)</option>
                                    <option value="1" {{ $env === '1' ? 'selected' : '' }}>PRODUCCIÓN (real)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">IVA general (%)</label>
                                <input type="number" step="0.01" name="iva_rate" class="form-control"
                                       value="{{ $settings['iva_rate'] ?? '19' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Impuesto al Consumo (%)</label>
                                <input type="number" step="0.01" name="ico_rate" class="form-control"
                                       value="{{ $settings['ico_rate'] ?? '0' }}">
                                <small class="text-muted">8% en restaurantes (si aplica)</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Test Set ID (Habilitación)</label>
                                <input type="text" name="dian_test_set_id" class="form-control"
                                       value="{{ $settings['dian_test_set_id'] ?? '' }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Software ID</label>
                                <input type="text" name="dian_software_id" class="form-control"
                                       value="{{ $settings['dian_software_id'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Software PIN</label>
                                <input type="password" name="dian_software_pin" class="form-control"
                                       value="{{ $settings['dian_software_pin'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Clave Técnica</label>
                                <input type="text" name="dian_clave_tecnica" class="form-control"
                                       value="{{ $settings['dian_clave_tecnica'] ?? '' }}">
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-2 text-secondary">Resolución DIAN</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Nº Resolución</label>
                                <input type="text" name="dian_resolucion_numero" class="form-control"
                                       value="{{ $settings['dian_resolucion_numero'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Prefijo</label>
                                <input type="text" name="dian_resolucion_prefijo" class="form-control"
                                       value="{{ $settings['dian_resolucion_prefijo'] ?? 'SETP' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Rango desde</label>
                                <input type="number" name="dian_resolucion_rango_desde" class="form-control"
                                       value="{{ $settings['dian_resolucion_rango_desde'] ?? '1' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Rango hasta</label>
                                <input type="number" name="dian_resolucion_rango_hasta" class="form-control"
                                       value="{{ $settings['dian_resolucion_rango_hasta'] ?? '5000000' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Vigencia desde</label>
                                <input type="date" name="dian_resolucion_fecha_desde" class="form-control"
                                       value="{{ $settings['dian_resolucion_fecha_desde'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Vigencia hasta</label>
                                <input type="date" name="dian_resolucion_fecha_hasta" class="form-control"
                                       value="{{ $settings['dian_resolucion_fecha_hasta'] ?? '' }}">
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-2 text-secondary">Certificado Digital (.p12 / .pfx)</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-shield-lock"></i> Subir certificado
                                </label>
                                <input type="file" name="dian_cert_file" class="form-control" accept=".p12,.pfx">
                                @if($tieneCert)
                                    <small class="text-success">
                                        <i class="bi bi-check-circle"></i>
                                        Cargado: <code>{{ $settings['dian_cert_path'] }}</code>
                                    </small>
                                @else
                                    <small class="text-warning">
                                        Sin certificado. Para Habilitación puedes usar uno de prueba; para Producción
                                        usa el .p12 oficial emitido por una entidad autorizada (Andes SCD, Certicámara, GSE, etc.).
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Contraseña del .p12</label>
                                <input type="password" name="dian_cert_password" class="form-control"
                                       value="{{ $settings['dian_cert_password'] ?? '' }}">
                            </div>
                        </div>

                        <div class="mt-5 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5 fw-bold shadow">
                                <i class="bi bi-save me-2"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
