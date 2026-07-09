"use strict";
(function () {
    var almacenes = [];
    var almacenesActivos = [];
    var ubicaciones = [];
    var tipos = ["punto_venta", "sucursal", "bodega", "principal", "transito", "devoluciones", "merma", "cuarentena"];
    function $(id) { return document.getElementById(id); }
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }
    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function checked(id) { return $(id).checked ? 1 : 0; }
    function setChecked(id, value) { $(id).checked = Number(value || 0) === 1; }
    function opcionesTipos() {
        return tipos.map(function (tipo) {
            return "<option value=\"" + tipo + "\">" + tipo.replace("_", " ") + "</option>";
        }).join("");
    }
    function opcionesAlmacenes(selected) {
        return "<option value=\"\">Seleccionar almacen</option>" + almacenesActivos.filter(function (item) {
            return item.estatus === "activo";
        }).map(function (item) {
            var sel = String(selected || "") === String(item.id_almacen) ? " selected" : "";
            return "<option value=\"" + escapeHtml(item.id_almacen) + "\"" + sel + ">" + escapeHtml(item.codigo_almacen || item.id_almacen) + " - " + escapeHtml(item.almacen) + "</option>";
        }).join("");
    }
    function cargarAlmacenesActivos() {
        return request("/almacen/almacenes_configuracion_erp?estatus=activo").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            almacenesActivos = response.depurar || [];
            $("alm_ubi_almacen").innerHTML = opcionesAlmacenes($("alm_ubi_almacen").value);
            $("alm_ubi_filtro_almacen").innerHTML = "<option value=\"\">Todos los almacenes activos</option>" + opcionesAlmacenes($("alm_ubi_filtro_almacen").value).replace("<option value=\"\">Seleccionar almacen</option>", "");
        });
    }
    function cargarAlmacenes() {
        var params = new URLSearchParams({q: $("alm_cfg_buscar").value.trim(), estatus: $("alm_cfg_filtro_estatus").value});
        return request("/almacen/almacenes_configuracion_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            almacenes = response.depurar || [];
            renderAlmacenes();
        });
    }
    function cargarUbicaciones() {
        var params = new URLSearchParams({id_almacen: $("alm_ubi_filtro_almacen").value, estatus: $("alm_ubi_filtro_estatus").value});
        return request("/almacen/ubicaciones_configuracion_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            ubicaciones = response.depurar || [];
            renderUbicaciones();
        });
    }
    function renderAlmacenes() {
        $("alm_cfg_body").innerHTML = almacenes.map(function (item, index) {
            var ops = [];
            if (Number(item.permite_recepcion) === 1) { ops.push("Recepcion"); }
            if (Number(item.permite_venta) === 1) { ops.push("Venta"); }
            if (Number(item.permite_preparacion) === 1) { ops.push("Preparacion"); }
            if (Number(item.es_tecnico) === 1) { ops.push("Tecnico"); }
            var badge = item.estatus === "activo" ? "badge-light-success" : "badge-light-danger";
            return "<tr><td><span class=\"fw-bold\">" + escapeHtml(item.codigo_almacen || "-") + "</span></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.almacen) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.calle || "") + " " + escapeHtml(item.numero_exterior || "") + "</div></td>" +
                "<td>" + escapeHtml(item.tipo_almacen || "-") + "</td>" +
                "<td>" + (ops.map(function (op) { return "<span class=\"badge badge-light-primary me-1\">" + escapeHtml(op) + "</span>"; }).join("") || "-") + "</td>" +
                "<td><span class=\"badge " + badge + "\">" + escapeHtml(item.estatus || "") + "</span></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" data-alm-editar=\"" + index + "\" type=\"button\"><i class=\"bi bi-pencil\"></i></button></td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin almacenes</td></tr>";
    }
    function renderUbicaciones() {
        $("alm_ubi_body").innerHTML = ubicaciones.map(function (item, index) {
            var badge = item.estatus === "activa" ? "badge-light-success" : "badge-light-danger";
            var detalle = [item.zona, item.pasillo, item.rack, item.nivel, item.contenedor].filter(Boolean).join(" / ");
            return "<tr><td><span class=\"fw-bold\">" + escapeHtml(item.codigo_ubicacion) + "</span></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre || "-") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                "<td>" + escapeHtml(detalle || "-") + "</td>" +
                "<td><span class=\"badge " + badge + "\">" + escapeHtml(item.estatus) + "</span></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" data-ubi-editar=\"" + index + "\" type=\"button\"><i class=\"bi bi-pencil\"></i></button></td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin ubicaciones</td></tr>";
    }
    function limpiarAlmacen() {
        ["alm_cfg_id", "alm_cfg_codigo", "alm_cfg_nombre", "alm_cfg_nombre_comercial", "alm_cfg_pais", "alm_cfg_estado", "alm_cfg_municipio", "alm_cfg_ciudad", "alm_cfg_colonia", "alm_cfg_cp", "alm_cfg_calle", "alm_cfg_numero", "alm_cfg_contacto", "alm_cfg_telefono", "alm_cfg_email", "alm_cfg_referencias", "alm_cfg_observaciones"].forEach(function (id) { $(id).value = ""; });
        $("alm_cfg_tipo").value = "punto_venta";
        $("alm_cfg_estatus").value = "activo";
        $("alm_cfg_orden").value = "100";
        ["alm_cfg_recepcion", "alm_cfg_ajustes"].forEach(function (id) { $(id).checked = true; });
        ["alm_cfg_venta", "alm_cfg_preparacion", "alm_cfg_tecnico"].forEach(function (id) { $(id).checked = false; });
    }
    function editarAlmacen(index) {
        var item = almacenes[index];
        if (!item) { return; }
        $("alm_cfg_id").value = item.id_almacen || "";
        $("alm_cfg_codigo").value = item.codigo_almacen || "";
        $("alm_cfg_nombre").value = item.almacen || "";
        $("alm_cfg_nombre_comercial").value = item.nombre_comercial || "";
        $("alm_cfg_tipo").value = item.tipo_almacen || "punto_venta";
        $("alm_cfg_estatus").value = item.estatus || "activo";
        $("alm_cfg_orden").value = item.orden || 100;
        $("alm_cfg_pais").value = item.pais || "";
        $("alm_cfg_estado").value = item.estado || "";
        $("alm_cfg_municipio").value = item.municipio || "";
        $("alm_cfg_ciudad").value = item.ciudad || "";
        $("alm_cfg_colonia").value = item.colonia || "";
        $("alm_cfg_cp").value = item.codigo_postal || "";
        $("alm_cfg_calle").value = item.calle || "";
        $("alm_cfg_numero").value = item.numero_exterior || "";
        $("alm_cfg_contacto").value = item.contacto_recepcion || "";
        $("alm_cfg_telefono").value = item.telefono_recepcion || "";
        $("alm_cfg_email").value = item.email_recepcion || "";
        $("alm_cfg_referencias").value = item.referencias_direccion || "";
        $("alm_cfg_observaciones").value = item.observaciones || "";
        setChecked("alm_cfg_recepcion", item.permite_recepcion);
        setChecked("alm_cfg_venta", item.permite_venta);
        setChecked("alm_cfg_preparacion", item.permite_preparacion);
        setChecked("alm_cfg_ajustes", item.permite_ajustes);
        setChecked("alm_cfg_tecnico", item.es_tecnico);
    }
    function guardarAlmacen() {
        post("/almacen/almacen_configuracion_guardar_erp", {
            id_almacen: $("alm_cfg_id").value,
            codigo_almacen: $("alm_cfg_codigo").value,
            almacen: $("alm_cfg_nombre").value,
            nombre_comercial: $("alm_cfg_nombre_comercial").value,
            tipo_almacen: $("alm_cfg_tipo").value,
            estatus: $("alm_cfg_estatus").value,
            orden: $("alm_cfg_orden").value,
            pais: $("alm_cfg_pais").value,
            estado: $("alm_cfg_estado").value,
            municipio: $("alm_cfg_municipio").value,
            ciudad: $("alm_cfg_ciudad").value,
            colonia: $("alm_cfg_colonia").value,
            codigo_postal: $("alm_cfg_cp").value,
            calle: $("alm_cfg_calle").value,
            numero_exterior: $("alm_cfg_numero").value,
            contacto_recepcion: $("alm_cfg_contacto").value,
            telefono_recepcion: $("alm_cfg_telefono").value,
            email_recepcion: $("alm_cfg_email").value,
            referencias_direccion: $("alm_cfg_referencias").value,
            permite_recepcion: checked("alm_cfg_recepcion"),
            permite_venta: checked("alm_cfg_venta"),
            permite_preparacion: checked("alm_cfg_preparacion"),
            permite_ajustes: checked("alm_cfg_ajustes"),
            es_tecnico: checked("alm_cfg_tecnico"),
            observaciones: $("alm_cfg_observaciones").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            limpiarAlmacen();
            return cargarAlmacenesActivos().then(cargarAlmacenes);
        }).catch(function (error) { Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"}); });
    }
    function limpiarUbicacion() {
        ["alm_ubi_id", "alm_ubi_codigo", "alm_ubi_nombre", "alm_ubi_zona", "alm_ubi_pasillo", "alm_ubi_rack", "alm_ubi_nivel", "alm_ubi_contenedor", "alm_ubi_descripcion"].forEach(function (id) { $(id).value = ""; });
        $("alm_ubi_estatus").value = "activa";
    }
    function editarUbicacion(index) {
        var item = ubicaciones[index];
        if (!item) { return; }
        $("alm_ubi_id").value = item.id_ubicacion || "";
        $("alm_ubi_almacen").value = item.id_almacen || "";
        $("alm_ubi_codigo").value = item.codigo_ubicacion || "";
        $("alm_ubi_nombre").value = item.nombre || "";
        $("alm_ubi_zona").value = item.zona || "";
        $("alm_ubi_pasillo").value = item.pasillo || "";
        $("alm_ubi_rack").value = item.rack || "";
        $("alm_ubi_nivel").value = item.nivel || "";
        $("alm_ubi_contenedor").value = item.contenedor || "";
        $("alm_ubi_descripcion").value = item.descripcion || "";
        $("alm_ubi_estatus").value = item.estatus || "activa";
    }
    function guardarUbicacion() {
        post("/almacen/ubicacion_configuracion_guardar_erp", {
            id_ubicacion: $("alm_ubi_id").value,
            id_almacen: $("alm_ubi_almacen").value,
            codigo_ubicacion: $("alm_ubi_codigo").value,
            nombre: $("alm_ubi_nombre").value,
            zona: $("alm_ubi_zona").value,
            pasillo: $("alm_ubi_pasillo").value,
            rack: $("alm_ubi_rack").value,
            nivel: $("alm_ubi_nivel").value,
            contenedor: $("alm_ubi_contenedor").value,
            descripcion: $("alm_ubi_descripcion").value,
            estatus: $("alm_ubi_estatus").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            limpiarUbicacion();
            return cargarUbicaciones();
        }).catch(function (error) { Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"}); });
    }
    document.addEventListener("click", function (event) {
        var alm = event.target.closest("[data-alm-editar]");
        if (alm) { editarAlmacen(parseInt(alm.getAttribute("data-alm-editar"), 10)); }
        var ubi = event.target.closest("[data-ubi-editar]");
        if (ubi) { editarUbicacion(parseInt(ubi.getAttribute("data-ubi-editar"), 10)); }
    });
    $("alm_cfg_tipo").innerHTML = opcionesTipos();
    $("alm_cfg_guardar").addEventListener("click", guardarAlmacen);
    $("alm_cfg_nuevo").addEventListener("click", limpiarAlmacen);
    $("alm_cfg_buscar").addEventListener("input", function () { clearTimeout(window.__almCfgBuscar); window.__almCfgBuscar = setTimeout(cargarAlmacenes, 300); });
    $("alm_cfg_filtro_estatus").addEventListener("change", cargarAlmacenes);
    $("alm_ubi_guardar").addEventListener("click", guardarUbicacion);
    $("alm_ubi_nuevo").addEventListener("click", limpiarUbicacion);
    $("alm_ubi_filtro_almacen").addEventListener("change", cargarUbicaciones);
    $("alm_ubi_filtro_estatus").addEventListener("change", cargarUbicaciones);
    limpiarAlmacen();
    cargarAlmacenesActivos().then(cargarAlmacenes).then(cargarUbicaciones).catch(function (error) {
        Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
    });
})();
