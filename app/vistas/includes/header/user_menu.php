<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-22
 * Proposito: renderizar el menu de usuario del navbar con datos reales de sesion.
 * Impacto: Layout ERP; reemplaza enlaces demo por rutas internas y cierre de sesion funcional.
 * Contrato: solo lee `$_SESSION` y permisos; no consulta BD ni expone datos sensibles.
 */
$navbarNombreBase = trim(implode(" ", array_filter(array(
  isset($_SESSION["nombres"]) ? $_SESSION["nombres"] : "",
  isset($_SESSION["apellido_paterno"]) ? $_SESSION["apellido_paterno"] : ""
))));
$navbarNombreMostrar = trim(isset($_SESSION["nombre_mostrar"]) ? $_SESSION["nombre_mostrar"] : "");
if ($navbarNombreMostrar === "") {
  $navbarNombreMostrar = $navbarNombreBase !== "" ? $navbarNombreBase : "Usuario ERP";
}
$navbarAlias = trim(isset($_SESSION["alias"]) ? $_SESSION["alias"] : "");
$navbarCorreo = trim(isset($_SESSION["correo"]) ? $_SESSION["correo"] : "");
$navbarPuesto = trim(isset($_SESSION["puesto"]) ? $_SESSION["puesto"] : "");
$navbarRoles = isset($_SESSION["roles"]) && is_array($_SESSION["roles"]) ? $_SESSION["roles"] : array();
$navbarRolPrincipal = count($navbarRoles) > 0 ? reset($navbarRoles) : "Sin rol";
$navbarDetalle = $navbarCorreo !== "" ? $navbarCorreo : ($navbarAlias !== "" ? "@" . $navbarAlias : "Usuario #" . intval(isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0));
$navbarPartesNombre = preg_split("/\s+/", trim($navbarNombreMostrar));
$navbarIniciales = "";
foreach ($navbarPartesNombre as $parteNombre) {
  if ($parteNombre !== "") {
    $navbarIniciales .= strtoupper(substr($parteNombre, 0, 1));
  }
  if (strlen($navbarIniciales) >= 2) {
    break;
  }
}
if ($navbarIniciales === "") {
  $navbarIniciales = "U";
}
?>
<!--begin::User menu-->
<div class="app-navbar-item ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
  <!--begin::Menu wrapper-->
  <div class="cursor-pointer symbol symbol-35px symbol-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
    <div class="symbol-label bg-light-primary text-primary fw-bold"><?= htmlspecialchars($navbarIniciales, ENT_QUOTES, "UTF-8") ?></div>
  </div>
  <!--begin::User account menu-->
  <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-300px" data-kt-menu="true">
    <div class="menu-item px-3">
      <div class="menu-content d-flex align-items-center px-3">
        <div class="symbol symbol-50px me-5">
          <div class="symbol-label bg-light-primary text-primary fw-bold fs-4"><?= htmlspecialchars($navbarIniciales, ENT_QUOTES, "UTF-8") ?></div>
        </div>
        <div class="d-flex flex-column">
          <div class="fw-bold d-flex align-items-center fs-5">
            <?= htmlspecialchars($navbarNombreMostrar, ENT_QUOTES, "UTF-8") ?>
            <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2"><?= htmlspecialchars($navbarRolPrincipal, ENT_QUOTES, "UTF-8") ?></span>
          </div>
          <span class="fw-semibold text-muted fs-7"><?= htmlspecialchars($navbarDetalle, ENT_QUOTES, "UTF-8") ?></span>
          <?php if ($navbarPuesto !== ""): ?>
            <span class="text-muted fs-8"><?= htmlspecialchars($navbarPuesto, ENT_QUOTES, "UTF-8") ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="separator my-2"></div>
    <?php if (SesionSeguridad::tienePermiso("seguridad.ver")): ?>
      <div class="menu-item px-5">
        <a href="/sistema/seguridad" class="menu-link px-5">Usuarios y roles</a>
      </div>
    <?php endif; ?>
    <?php if (SesionSeguridad::tienePermiso("notificaciones.ver")): ?>
      <div class="menu-item px-5">
        <a href="/sistema/notificaciones" class="menu-link px-5">Notificaciones</a>
      </div>
    <?php endif; ?>
    <div class="menu-item px-5">
      <span class="menu-link px-5 text-muted">Sesion activa</span>
    </div>
    <div class="separator my-2"></div>
    <div class="menu-item px-5">
      <a href="/autenticacion/cerrar_session" class="menu-link px-5 text-danger">Cerrar sesion</a>
    </div>
  </div>
  <!--end::User account menu-->
  <!--end::Menu wrapper-->
</div>
<!--end::User menu-->
