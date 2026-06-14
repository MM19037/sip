<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 8mm 10mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; padding: 8mm 10mm; }

    .header { background: #0f2744; color: #fff; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .header-left .sistema { font-size: 8px; color: #93c5fd; letter-spacing: 1px; text-transform: uppercase; }
    .header-left .titulo { font-size: 15px; font-weight: 700; margin-top: 2px; }
    .header-right { text-align: right; font-size: 8px; color: #93c5fd; }

    .resumen { display: flex; gap: 10px; margin-bottom: 14px; }
    .resumen-card { flex: 1; background: #f1f5f9; border-left: 3px solid #0f7ef4; padding: 8px 10px; border-radius: 3px; }
    .resumen-card .valor { font-size: 14px; font-weight: 700; color: #0f2744; }
    .resumen-card .label { font-size: 8px; color: #64748b; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #0f2744; color: #fff; }
    thead th { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: 600; letter-spacing: 0.3px; }
    thead th.r { text-align: right; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr:hover { background: #e0f2fe; }
    tbody td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
    tbody td.r { text-align: right; }
    tbody td.c { text-align: center; }
    tbody td.bold { font-weight: 700; }
    tbody td.green { color: #16a34a; }
    tbody td.red { color: #dc2626; }
    tbody td.blue { color: #2563eb; }

    .footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: right; }
</style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="sistema">SIP — Sistema Integral de Pedidos</div>
            <div class="titulo">{{ $titulo }}</div>
        </div>
        <div class="header-right">
            Generado: {{ $fecha }}<br>
            @yield('header-extra')
        </div>
    </div>

    @yield('resumen')

    @yield('contenido')

    <div class="footer">
        SIP — Sistema Integral de Pedidos &nbsp;|&nbsp; {{ $fecha }}
    </div>
</body>
</html>
