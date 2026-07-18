# Entorno Local XAMPP Panel De Control

## Proyecto Canonico

- Ruta del proyecto nuevo: `C:\xampp\htdocs\panel_de_control`.
- Host local canonico: `http://panel.com.local/`.
- No usar `C:\xampp\htdocs\panel` para este proyecto salvo indicacion explicita del dueno.
- No usar `localhost` para pruebas funcionales del ERP/POS si existe mas de un proyecto bajo XAMPP.

## Hosts De Windows

En Windows, agregar el host local en:

```text
C:\Windows\System32\drivers\etc\hosts
```

Entrada esperada:

```text
127.0.0.1 panel.com.local
```

El archivo requiere permisos de administrador para editarse.

## VirtualHost En XAMPP/Apache

Configurar Apache para que `panel.com.local` apunte al `public` del proyecto nuevo:

```apache
<VirtualHost *:80>
    ServerName panel.com.local
    DocumentRoot "C:/xampp/htdocs/panel_de_control/public"

    <Directory "C:/xampp/htdocs/panel_de_control/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Despues de cambiar la configuracion, reiniciar Apache desde XAMPP.

## Regla Para Agentes

Antes de editar ERP/POS, confirmar:

```powershell
Get-Location
```

Debe estar en:

```text
C:\xampp\htdocs\panel_de_control
```

Si una herramienta queda en `C:\xampp\htdocs\panel`, cambiar `workdir` al proyecto canonico antes de leer, editar o ejecutar scripts.
