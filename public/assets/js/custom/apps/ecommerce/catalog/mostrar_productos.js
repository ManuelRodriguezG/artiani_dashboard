// On document ready
KTUtil.onDOMContentLoaded(function () {
//    KTAppEcommerceProducts.init();
    consultar_productos();
});

function consultar_productos() {
    let data = "";
    let codigo_producto = "";
    let productos = listar(data);
    console.log(productos);
    productos = JSON.parse(productos);
    if (productos.error == false) {
        productos.depurar.map(function (producto) {
            
            codigo_producto += `
                        <tr>
                            <!--begin::Checkbox-->
                            <td>
                              <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1">
                              </div>
                            </td>
                            <!--end::Checkbox-->
                            <!--begin::Category=-->
                            <td>
                              <div class="d-flex align-items-center">
                                <!--begin::Thumbnail-->
                                <a href="/producto/editar/${producto.id_producto}" class="symbol symbol-50px">
                                  <span class="symbol-label" style="background-image:url('${producto.url_imagen}');"></span>
                                </a>
                                <!--end::Thumbnail-->
                                <div class="ms-5">
                                  <!--begin::Title-->
                                  <a href="/producto/editar/${producto.id_producto}" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">
                                    ${producto.nombre}
                                   </a>
                                  <!--end::Title-->
                                </div>
                              </div>
                            </td>
                            <!--end::Category=-->
                            <!--begin::SKU=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold">
                                ${producto.sku}
                              </span>
                            </td>
                            <!--end::SKU=-->
                            <!--begin::Qty=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold text-warning ms-3">
                                ${producto.existencia}
                              </span>
                            </td>
                            <!--end::Qty=-->
                            <!--begin::Price=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold text-dark">$${producto.precio_base}</span>
                            </td>
                            <!--end::Price=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0">
                              <!--begin::Badges-->
                              <div class="badge badge-light-${producto.estatus == 1 ? 'success' : 'danger'}">${producto.estatus == 1 ? 'Activo' : 'Inactivo'}</div>
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Action=-->
                            <td class="text-end">
                              <a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                <!--begin::Svg Icon | path: icons/duotune/arrows/arr072.svg-->
                                <span class="svg-icon svg-icon-5 m-0">
                                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z" fill="currentColor"></path>
                                  </svg>
                                </span>
                                <!--end::Svg Icon--></a>
                              <!--begin::Menu-->
                              <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                  <a href="/producto/editar/${producto.id_producto}" class="menu-link px-3">Edit</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                  <a href="#" class="menu-link px-3" data-kt-ecommerce-product-filter="delete_row">Delete</a>
                                </div>
                                <!--end::Menu item-->
                              </div>
                              <!--end::Menu-->
                            </td>
                            <!--end::Action=-->
                          </tr>
            `;
        });
        $("#kt_ecommerce_products_table tbody").html(codigo_producto);
        KTMenu.createInstances();
        KTAppEcommerceProducts.init();
    }

}