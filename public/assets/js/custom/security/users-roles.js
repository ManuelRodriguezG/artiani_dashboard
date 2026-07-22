"use strict";

(function () {
    var usuarios = [];
    var roles = [];
    var tbody;
    var auditTbody;
    var buscar;
    var rolePermissionSelect;
    var permissionContainer;
    var permissionSummary;
    var canAdminister = false;

    function request(url, data) {
        var headers = data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"} : {};
        if (data && window.ERP_CSRF_TOKEN) {
            headers["X-CSRF-Token"] = window.ERP_CSRF_TOKEN;
        }
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: headers,
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) {
            return response.json();
        });
    }

    function cargar() {
        var solicitudes = [
            request("/sistema/seguridad_usuarios_roles_listar"),
            request("/sistema/seguridad_roles_listar")
        ];
        if (auditTbody) {
            solicitudes.push(request(auditoriaUrl()));
        }

        Promise.all(solicitudes).then(function (responses) {
            usuarios = responses[0].depurar || [];
            roles = responses[1].depurar || [];
            renderRoleOptions();
            render();
            if (auditTbody) {
                renderAuditoria(responses[2].depurar || []);
            }
        });
    }

    function auditoriaUrl() {
        var params = new URLSearchParams({limite: "100"});
        [
            ["usuario", "seguridad_auditoria_usuario"],
            ["modulo", "seguridad_auditoria_modulo"],
            ["accion", "seguridad_auditoria_accion"],
            ["resultado", "seguridad_auditoria_resultado"],
            ["fecha_desde", "seguridad_auditoria_desde"],
            ["fecha_hasta", "seguridad_auditoria_hasta"]
        ].forEach(function (filtro) {
            var input = document.getElementById(filtro[1]);
            if (input && input.value) {
                params.set(filtro[0], input.value);
            }
        });
        return "/sistema/seguridad_auditoria_listar?" + params.toString();
    }

    function renderRoleOptions() {
        if (!rolePermissionSelect) {
            return;
        }
        var actual = rolePermissionSelect.value;
        rolePermissionSelect.innerHTML = roles.map(function (rol) {
            var descripcion = rol.descripcion ? " - " + rol.descripcion : "";
            return "<option value=\"" + rol.id_rol + "\">" + escapeHtml(rol.rol + descripcion) +
                " (" + escapeHtml(rol.total_permisos || 0) + ")</option>";
        }).join("");
        if (actual && roles.some(function (rol) { return String(rol.id_rol) === String(actual); })) {
            rolePermissionSelect.value = actual;
        }
        if (rolePermissionSelect.value) {
            cargarPermisosRol();
        }
    }

    function cargarPermisosRol() {
        if (!rolePermissionSelect || !rolePermissionSelect.value) {
            return;
        }
        request("/sistema/seguridad_rol_permisos_consultar?id_rol=" + rolePermissionSelect.value)
            .then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                renderPermisos(response.depurar);
            }).catch(mostrarError);
    }

    function renderPermisos(data) {
        var grupos = {};
        (data.permisos || []).forEach(function (permiso) {
            if (!grupos[permiso.modulo]) {
                grupos[permiso.modulo] = [];
            }
            grupos[permiso.modulo].push(permiso);
        });
        var total = (data.permisos || []).filter(function (p) { return String(p.asignado) === "1"; }).length;
        permissionSummary.textContent = data.rol.rol + ": " + total + " permisos asignados";
        permissionContainer.innerHTML = Object.keys(grupos).map(function (modulo) {
            var permisos = grupos[modulo];
            var todos = permisos.every(function (p) { return String(p.asignado) === "1"; });
            return "<div class=\"col-md-6 col-xl-4\"><div class=\"border p-5 h-100\">" +
                "<div class=\"d-flex justify-content-between align-items-center mb-4\"><h3 class=\"fs-6 fw-bold text-capitalize mb-0\">" +
                escapeHtml(modulo) + "</h3><label class=\"form-check form-check-sm\"><input class=\"form-check-input\" type=\"checkbox\" data-modulo-todos=\"" +
                escapeHtml(modulo) + "\"" + (todos ? " checked" : "") + (canAdminister ? "" : " disabled") +
                "><span class=\"form-check-label text-muted\">Todos</span></label></div>" +
                permisos.map(function (p) {
                    return "<label class=\"form-check form-check-custom form-check-solid mb-3\"><input class=\"form-check-input\" type=\"checkbox\" data-permiso-id=\"" +
                        p.id_permiso + "\" data-permiso-modulo=\"" + escapeHtml(modulo) + "\"" + (String(p.asignado) === "1" ? " checked" : "") +
                        (canAdminister ? "" : " disabled") +
                        "><span class=\"form-check-label\"><span class=\"fw-semibold\">" + escapeHtml(p.permiso) +
                        "</span><span class=\"d-block text-muted fs-8\">" + escapeHtml(p.descripcion || "") + "</span></span></label>";
                }).join("") + "</div></div>";
        }).join("");
    }

    function guardarPermisosRol() {
        var permisos = Array.prototype.slice.call(permissionContainer.querySelectorAll("[data-permiso-id]:checked"))
            .map(function (input) { return Number(input.getAttribute("data-permiso-id")); });
        Swal.fire({
            text: "Se reemplazaran los permisos actuales del rol seleccionado.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Guardar permisos",
            cancelButtonText: "Conservar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return null;
            }
            return request("/sistema/seguridad_rol_permisos_guardar", {
                id_rol: rolePermissionSelect.value,
                permisos: JSON.stringify(permisos)
            });
        }).then(function (response) {
            if (!response) {
                return null;
            }
            if (response.error) {
                throw new Error(response.mensaje);
            }
            return Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
        }).then(function (result) {
            if (result) {
                cargar();
            }
        }).catch(mostrarError);
    }

    function abrirNuevoUsuario() {
        var opcionesRoles = "<option value=\"0\">Sin rol inicial</option>" + roles.map(function (rol) {
            return "<option value=\"" + rol.id_rol + "\">" + escapeHtml(rol.rol) + "</option>";
        }).join("");
        Swal.fire({
            title: "Nuevo usuario",
            html:
                "<div class=\"text-start\">" +
                "<label class=\"form-label\">Nombres</label>" +
                "<input id=\"seg_nuevo_nombres\" class=\"form-control mb-3\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Apellido paterno</label>" +
                "<input id=\"seg_nuevo_apellido_paterno\" class=\"form-control mb-3\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Apellido materno</label>" +
                "<input id=\"seg_nuevo_apellido_materno\" class=\"form-control mb-3\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Celular</label>" +
                "<input id=\"seg_nuevo_celular\" class=\"form-control mb-3\" inputmode=\"numeric\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Usuario / alias</label>" +
                "<input id=\"seg_nuevo_alias\" class=\"form-control mb-3\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Correo</label>" +
                "<input id=\"seg_nuevo_correo\" class=\"form-control mb-3\" type=\"email\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Telefono</label>" +
                "<input id=\"seg_nuevo_telefono\" class=\"form-control mb-3\" inputmode=\"tel\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Nombre para mostrar</label>" +
                "<input id=\"seg_nuevo_nombre_mostrar\" class=\"form-control mb-3\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Area / departamento</label>" +
                "<input id=\"seg_nuevo_area_departamento\" class=\"form-control mb-3\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Puesto</label>" +
                "<input id=\"seg_nuevo_puesto\" class=\"form-control mb-3\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Telefono secundario</label>" +
                "<input id=\"seg_nuevo_telefono_secundario\" class=\"form-control mb-3\" inputmode=\"tel\" autocomplete=\"off\">" +
                "<label class=\"form-label\">Contrasena inicial</label>" +
                "<input id=\"seg_nuevo_contrasenia\" class=\"form-control mb-3\" type=\"password\" autocomplete=\"new-password\">" +
                "<label class=\"form-label\">Confirmar contrasena</label>" +
                "<input id=\"seg_nuevo_confirmar\" class=\"form-control mb-3\" type=\"password\" autocomplete=\"new-password\">" +
                "<label class=\"form-label\">Rol inicial</label>" +
                "<select id=\"seg_nuevo_rol\" class=\"form-select\">" + opcionesRoles + "</select>" +
                "</div>",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Crear usuario",
            cancelButtonText: "Cancelar",
            focusConfirm: false,
            preConfirm: function () {
                var datos = {
                    nombres: document.getElementById("seg_nuevo_nombres").value.trim(),
                    apellido_paterno: document.getElementById("seg_nuevo_apellido_paterno").value.trim(),
                    apellido_materno: document.getElementById("seg_nuevo_apellido_materno").value.trim(),
                    celular: document.getElementById("seg_nuevo_celular").value.trim(),
                    alias: document.getElementById("seg_nuevo_alias").value.trim(),
                    correo: document.getElementById("seg_nuevo_correo").value.trim(),
                    telefono: document.getElementById("seg_nuevo_telefono").value.trim(),
                    nombre_mostrar: document.getElementById("seg_nuevo_nombre_mostrar").value.trim(),
                    area_departamento: document.getElementById("seg_nuevo_area_departamento").value.trim(),
                    puesto: document.getElementById("seg_nuevo_puesto").value.trim(),
                    telefono_secundario: document.getElementById("seg_nuevo_telefono_secundario").value.trim(),
                    notas_admin: "",
                    contrasenia: document.getElementById("seg_nuevo_contrasenia").value,
                    confirmar_contrasenia: document.getElementById("seg_nuevo_confirmar").value,
                    id_rol: document.getElementById("seg_nuevo_rol").value
                };
                if (!datos.nombres || !datos.celular || !datos.contrasenia) {
                    Swal.showValidationMessage("Nombre, celular y contrasena son obligatorios");
                    return false;
                }
                if (datos.contrasenia.length < 8) {
                    Swal.showValidationMessage("La contrasena debe tener al menos 8 caracteres");
                    return false;
                }
                if (datos.contrasenia !== datos.confirmar_contrasenia) {
                    Swal.showValidationMessage("La contrasena no coincide");
                    return false;
                }
                return datos;
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return null;
            }
            return request("/sistema/seguridad_usuario_crear", result.value);
        }).then(function (response) {
            if (!response) {
                return null;
            }
            if (response.error) {
                throw new Error(response.mensaje);
            }
            return Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
        }).then(function (result) {
            if (result) {
                cargar();
            }
        }).catch(mostrarError);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-22
     * Proposito: editar perfil interno y permitir restablecer contrasena sin exponer secretos al cliente.
     * Impacto: UI de Seguridad/Usuarios; la contrasena es opcional y solo se envia si el operador la captura.
     */
    function abrirEditarUsuario(usuario) {
        Swal.fire({
            title: "Editar usuario",
            html:
                "<div class=\"text-start\">" +
                "<label class=\"form-label\">Nombres</label>" +
                "<input id=\"seg_edit_nombres\" class=\"form-control mb-3\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.nombres || "") + "\">" +
                "<label class=\"form-label\">Apellido paterno</label>" +
                "<input id=\"seg_edit_apellido_paterno\" class=\"form-control mb-3\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.apellido_paterno || "") + "\">" +
                "<label class=\"form-label\">Apellido materno</label>" +
                "<input id=\"seg_edit_apellido_materno\" class=\"form-control mb-3\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.apellido_materno || "") + "\">" +
                "<label class=\"form-label\">Celular</label>" +
                "<input id=\"seg_edit_celular\" class=\"form-control mb-3\" inputmode=\"numeric\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.celular || "") + "\">" +
                "<label class=\"form-label\">Usuario / alias</label>" +
                "<input id=\"seg_edit_alias\" class=\"form-control mb-3\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.alias || "") + "\">" +
                "<label class=\"form-label\">Correo</label>" +
                "<input id=\"seg_edit_correo\" class=\"form-control mb-3\" type=\"email\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.correo || "") + "\">" +
                "<label class=\"form-label\">Telefono</label>" +
                "<input id=\"seg_edit_telefono\" class=\"form-control mb-3\" inputmode=\"tel\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.telefono || "") + "\">" +
                "<label class=\"form-label\">Nombre para mostrar</label>" +
                "<input id=\"seg_edit_nombre_mostrar\" class=\"form-control mb-3\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.nombre_mostrar || "") + "\">" +
                "<label class=\"form-label\">Area / departamento</label>" +
                "<input id=\"seg_edit_area_departamento\" class=\"form-control mb-3\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.area_departamento || "") + "\">" +
                "<label class=\"form-label\">Puesto</label>" +
                "<input id=\"seg_edit_puesto\" class=\"form-control mb-3\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.puesto || "") + "\">" +
                "<label class=\"form-label\">Telefono secundario</label>" +
                "<input id=\"seg_edit_telefono_secundario\" class=\"form-control mb-3\" inputmode=\"tel\" autocomplete=\"off\" value=\"" + escapeHtml(usuario.telefono_secundario || "") + "\">" +
                "<label class=\"form-label\">Notas admin</label>" +
                "<textarea id=\"seg_edit_notas_admin\" class=\"form-control mb-3\" rows=\"2\">" + escapeHtml(usuario.notas_admin || "") + "</textarea>" +
                "<div class=\"separator my-4\"></div>" +
                "<div class=\"fw-bold mb-2\">Restablecer contrasena</div>" +
                "<div class=\"text-muted fs-8 mb-3\">Deja estos campos vacios para conservar la contrasena actual.</div>" +
                "<label class=\"form-label\">Nueva contrasena</label>" +
                "<input id=\"seg_edit_contrasenia\" class=\"form-control mb-3\" type=\"password\" autocomplete=\"new-password\">" +
                "<label class=\"form-label\">Confirmar nueva contrasena</label>" +
                "<input id=\"seg_edit_confirmar\" class=\"form-control mb-3\" type=\"password\" autocomplete=\"new-password\">" +
                "<label class=\"form-label\">Estado</label>" +
                "<select id=\"seg_edit_estatus\" class=\"form-select\">" +
                "<option value=\"1\"" + (String(usuario.estatus) === "1" ? " selected" : "") + ">Activo</option>" +
                "<option value=\"0\"" + (String(usuario.estatus) === "0" ? " selected" : "") + ">Inactivo</option>" +
                "</select>" +
                "</div>",
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Guardar cambios",
            cancelButtonText: "Cancelar",
            focusConfirm: false,
            preConfirm: function () {
                var datos = {
                    id_usuario: usuario.id_usuario,
                    nombres: document.getElementById("seg_edit_nombres").value.trim(),
                    apellido_paterno: document.getElementById("seg_edit_apellido_paterno").value.trim(),
                    apellido_materno: document.getElementById("seg_edit_apellido_materno").value.trim(),
                    celular: document.getElementById("seg_edit_celular").value.trim(),
                    alias: document.getElementById("seg_edit_alias").value.trim(),
                    correo: document.getElementById("seg_edit_correo").value.trim(),
                    telefono: document.getElementById("seg_edit_telefono").value.trim(),
                    nombre_mostrar: document.getElementById("seg_edit_nombre_mostrar").value.trim(),
                    area_departamento: document.getElementById("seg_edit_area_departamento").value.trim(),
                    puesto: document.getElementById("seg_edit_puesto").value.trim(),
                    telefono_secundario: document.getElementById("seg_edit_telefono_secundario").value.trim(),
                    notas_admin: document.getElementById("seg_edit_notas_admin").value.trim(),
                    contrasenia: document.getElementById("seg_edit_contrasenia").value,
                    confirmar_contrasenia: document.getElementById("seg_edit_confirmar").value,
                    estatus: document.getElementById("seg_edit_estatus").value
                };
                if (!datos.nombres || !datos.celular) {
                    Swal.showValidationMessage("Nombre y celular son obligatorios");
                    return false;
                }
                if (datos.contrasenia || datos.confirmar_contrasenia) {
                    if (datos.contrasenia.length < 8) {
                        Swal.showValidationMessage("La contrasena debe tener al menos 8 caracteres");
                        return false;
                    }
                    if (datos.contrasenia !== datos.confirmar_contrasenia) {
                        Swal.showValidationMessage("La contrasena no coincide");
                        return false;
                    }
                }
                return datos;
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return null;
            }
            return request("/sistema/seguridad_usuario_editar", result.value);
        }).then(function (response) {
            if (!response) {
                return null;
            }
            if (response.error) {
                throw new Error(response.mensaje);
            }
            return Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
        }).then(function (result) {
            if (result) {
                cargar();
            }
        }).catch(mostrarError);
    }

    function mostrarError(error) {
        Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"});
    }

    function render() {
        var filtro = buscar.value.trim().toLowerCase();
        tbody.innerHTML = usuarios.filter(function (usuario) {
            var texto = [usuario.nombres, usuario.apellido_paterno, usuario.apellido_materno, usuario.celular, usuario.roles].join(" ").toLowerCase();
            return !filtro || texto.indexOf(filtro) !== -1;
        }).map(function (usuario) {
            var opciones = roles.map(function (rol) {
                return "<option value=\"" + rol.id_rol + "\">" + escapeHtml(rol.rol) + "</option>";
            }).join("");
            var chips = (usuario.roles_detalle || "").split("|").filter(Boolean).map(function (detalle) {
                var partes = detalle.split(":");
                var idRol = partes.shift();
                var rol = partes.join(":");
                return canAdminister
                    ? "<button class=\"btn btn-sm badge badge-light-primary me-1\" title=\"Quitar rol\" data-quitar-rol=\"" + idRol + "\" data-usuario=\"" + usuario.id_usuario + "\">" +
                        escapeHtml(rol.trim()) + " <i class=\"bi bi-x\"></i></button>"
                    : "<span class=\"badge badge-light-primary me-1\">" + escapeHtml(rol.trim()) + "</span>";
            }).join("") || "<span class=\"text-muted\">Sin rol</span>";

            var detalleUsuario = [
                usuario.alias ? "@" + usuario.alias : "",
                usuario.correo || "",
                usuario.puesto || usuario.area_departamento || ""
            ].filter(Boolean).map(escapeHtml).join(" · ");

            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(usuario.nombre_mostrar || (usuario.nombres + " " + usuario.apellido_paterno)) + "</div>" +
                (detalleUsuario ? "<div class=\"text-muted fs-8\">" + detalleUsuario + "</div>" : "") + "</td>" +
                "<td>" + escapeHtml(usuario.celular) + "</td>" +
                "<td><span class=\"btn btn-sm " + (String(usuario.estatus) === "1" ? "btn-light-success\">Activo" : "btn-light-danger\">Inactivo") +
                "</span>" + (canAdminister ? " <button class=\"btn btn-sm btn-icon btn-light\" title=\"Cambiar estado\" data-estatus=\"" +
                (String(usuario.estatus) === "1" ? "0" : "1") + "\" data-usuario=\"" + usuario.id_usuario +
                "\"><i class=\"bi bi-power\"></i></button>" : "") + "</td>" +
                "<td>" + chips + "</td>" +
                "<td class=\"text-end\">" + (canAdminister ? "<div class=\"d-flex justify-content-end gap-2\">" +
                "<button class=\"btn btn-sm btn-light-primary\" data-editar-usuario=\"" + usuario.id_usuario + "\">Editar / contrasena</button>" +
                "<select class=\"form-select form-select-sm w-175px\" data-role-select>" + opciones + "</select>" +
                "<button class=\"btn btn-sm btn-primary\" data-asignar=\"" + usuario.id_usuario + "\"><i class=\"bi bi-plus-lg\"></i></button>" +
                "</div>" : "<span class=\"text-muted\">Solo consulta</span>") + "</td></tr>";
        }).join("");
    }

    function renderAuditoria(eventos) {
        auditTbody.innerHTML = eventos.map(function (evento) {
            var resultado = String(evento.resultado || "").toLowerCase();
            var claseResultado = resultado === "ok" || resultado === "success"
                ? "badge-light-success"
                : "badge-light-danger";
            var entidad = evento.entidad
                ? escapeHtml(evento.entidad) + (evento.entidad_id ? " #" + escapeHtml(evento.entidad_id) : "")
                : "";
            var detalle = [entidad, escapeHtml(evento.mensaje)].filter(Boolean).join("<br>");

            return "<tr>" +
                "<td class=\"text-nowrap\">" + escapeHtml(evento.fecha_registro) + "</td>" +
                "<td>" + escapeHtml(evento.usuario || "Sistema") + "</td>" +
                "<td>" + escapeHtml(evento.modulo) + "</td>" +
                "<td>" + escapeHtml(evento.accion) + "</td>" +
                "<td><span class=\"badge " + claseResultado + "\">" + escapeHtml(evento.resultado) + "</span></td>" +
                "<td>" + (detalle || "<span class=\"text-muted\">Sin detalle</span>") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin actividad registrada</td></tr>";
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    document.addEventListener("DOMContentLoaded", function () {
        tbody = document.getElementById("seguridad_usuarios_roles");
        auditTbody = document.getElementById("seguridad_auditoria");
        buscar = document.getElementById("seguridad_buscar");
        rolePermissionSelect = document.getElementById("seguridad_rol_permisos");
        permissionContainer = document.getElementById("seguridad_permisos_modulos");
        permissionSummary = document.getElementById("seguridad_permisos_resumen");
        canAdminister = document.getElementById("seguridad_puede_administrar").value === "1";
        if (!tbody || !buscar) {
            return;
        }
        buscar.addEventListener("input", render);
        if (rolePermissionSelect) {
            rolePermissionSelect.addEventListener("change", cargarPermisosRol);
        }
        if (permissionContainer) {
            if (!canAdminister) {
                permissionContainer.addEventListener("click", function (event) {
                    if (event.target.matches("input[type='checkbox']")) {
                        event.preventDefault();
                    }
                });
            }
            permissionContainer.addEventListener("change", function (event) {
                var modulo = event.target.getAttribute("data-modulo-todos");
                if (modulo != null) {
                    permissionContainer.querySelectorAll("[data-permiso-modulo=\"" + modulo + "\"]")
                        .forEach(function (input) { input.checked = event.target.checked; });
                }
            });
        }
        var savePermissions = document.getElementById("seguridad_guardar_permisos");
        if (savePermissions) {
            savePermissions.addEventListener("click", guardarPermisosRol);
        }
        var newUserButton = document.getElementById("seguridad_nuevo_usuario");
        if (newUserButton) {
            newUserButton.addEventListener("click", abrirNuevoUsuario);
        }
        var auditFilterButton = document.getElementById("seguridad_auditoria_filtrar");
        if (auditFilterButton) {
            auditFilterButton.addEventListener("click", function () {
                request(auditoriaUrl()).then(function (response) {
                    if (response.error) { throw new Error(response.mensaje); }
                    renderAuditoria(response.depurar || []);
                }).catch(mostrarError);
            });
        }
        tbody.addEventListener("click", function (event) {
            var statusButton = event.target.closest("[data-estatus]");
            if (statusButton) {
                var activar = statusButton.getAttribute("data-estatus") === "1";
                Swal.fire({
                    text: activar ? "Se activara el acceso del usuario." : "Se desactivara el acceso del usuario.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: activar ? "Activar usuario" : "Desactivar usuario",
                    cancelButtonText: "Cancelar"
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return null;
                    }
                    return request("/sistema/seguridad_usuario_estatus", {
                        id_usuario: statusButton.getAttribute("data-usuario"),
                        estatus: statusButton.getAttribute("data-estatus")
                    });
                }).then(function (response) {
                    if (!response) { return; }
                    if (response.error) { throw new Error(response.mensaje); }
                    cargar();
                }).catch(mostrarError);
                return;
            }

            var editButton = event.target.closest("[data-editar-usuario]");
            if (editButton) {
                var idUsuarioEditar = editButton.getAttribute("data-editar-usuario");
                var usuarioEditar = usuarios.find(function (usuario) {
                    return String(usuario.id_usuario) === String(idUsuarioEditar);
                });
                if (usuarioEditar) {
                    abrirEditarUsuario(usuarioEditar);
                }
                return;
            }

            var removeButton = event.target.closest("[data-quitar-rol]");
            if (removeButton) {
                Swal.fire({
                    text: "Se quitara este rol al usuario seleccionado.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Quitar rol",
                    cancelButtonText: "Cancelar"
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return null;
                    }
                    return request("/sistema/seguridad_usuario_rol_quitar", {
                        id_usuario: removeButton.getAttribute("data-usuario"),
                        id_rol: removeButton.getAttribute("data-quitar-rol")
                    });
                }).then(function (response) {
                    if (!response) { return; }
                    if (response.error) { throw new Error(response.mensaje); }
                    cargar();
                }).catch(mostrarError);
                return;
            }

            var button = event.target.closest("[data-asignar]");
            if (!button) {
                return;
            }
            var select = button.parentElement.querySelector("[data-role-select]");
            request("/sistema/seguridad_usuario_rol_asignar", {
                id_usuario: button.getAttribute("data-asignar"),
                id_rol: select.value
            }).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                cargar();
            }).catch(mostrarError);
        });
        cargar();
    });
})();
