let COMPRA_VENTA_P = {error: true};
let VARIANTE_REGISTRO = 0;
// Class definition
var KTAppEcommerceSaveProduct = function () {
    // Shared variables
    var table;
    var datatable;
    // Private functions

    // Init quill editor
    const initQuill = () => {
        // Define all elements for quill editor
        const elements = [
            '#kt_ecommerce_add_product_description',
            '#kt_ecommerce_add_product_meta_description'
        ];
        // Loop all elements
        elements.forEach(element => {
            // Get quill element
            let quill = document.querySelector(element);
            // Break if element not found
            if (!quill) {
                return;
            }

            // Init quill --- more info: https://quilljs.com/docs/quickstart/
            quill = new Quill(element, {
                modules: {
                    toolbar: [
                        [{
                                header: [1, 2, false]
                            }],
                        ['bold', 'italic', 'underline'],
                        ['image', 'code-block']
                    ]
                },
                placeholder: 'Type your text here...',
                theme: 'snow' // or 'bubble'
            });
        });
        table = document.querySelector('#kt_ecommerce_edit_order_product_table');
        table2 = document.querySelector('#kt_ecommerce_edit_order_product_table_complementos');
        datatable = $(table).DataTable({
            'order': [],
            "scrollY": "400px",
            "scrollCollapse": true,
            "paging": false,
            "info": false,
            'columnDefs': [
                {orderable: false, targets: 0}, // Disable ordering on column 0 (checkbox)
            ]
        });
        datatable2 = $(table2).DataTable({
            'order': [],
            "scrollY": "400px",
            "scrollCollapse": true,
            "paging": false,
            "info": false,
            'columnDefs': [
                {orderable: false, targets: 0}, // Disable ordering on column 0 (checkbox)
            ]
        });
    }


// Init form repeater --- more info: https://github.com/DubFriend/jquery.repeater
    const initFormRepeater = () => {
        $('#kt_ecommerce_add_product_options').repeater({
            initEmpty: false,
            defaultValues: {
                'text-input': 'foo'
            },
            show: function () {
                $(this).slideDown();
                // Init select2 on new repeated items
                initConditionsSelect2();
            },
            hide: function (deleteElement) {
                $(this).slideUp(deleteElement);
            }
        });
    }

// Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatable = () => {
        const filterSearch = document.querySelector('[data-kt-ecommerce-edit-order-filter="search"]');
        let respuesta = '', tabla_init = '', contador = 0;
        filterSearch.addEventListener('keyup', async function (e) {
//      console.log(e.target.value);
            console.log(contador);
            contador++;
            await setTimeout(async function () {
                console.log("settimeout-" + contador);
                contador--;
                if (contador == 0) {
                    console.log("peticion");
                    respuesta = await busqueda_productos(e.target.value);
//      console.log(respuesta);
                    if (respuesta.error == false) {
                        tabla_init = await ui_pedido_productos(respuesta.depurar);
                        console.log(tabla_init);
                        if (tabla_init) {
                            handleProductSelect();
                        }
                    } else {
                        if (tabla_init) {
                            tabla_init.search(e.target.value).draw();
                        }
                    }
                }
            }, 1000);
        });
    }

    var handleSearchDatatable2 = () => {
        const filterSearch = document.querySelector('[data-kt-ecommerce-edit-order-filter="search2"]');
        let respuesta = '', tabla_init = '', contador = 0;
        filterSearch.addEventListener('keyup', async function (e) {
//      console.log(e.target.value);
            console.log(contador);
            contador++;
            await setTimeout(async function () {
                console.log("settimeout-" + contador);
                contador--;
                if (contador == 0) {
                    console.log("peticion");
                    respuesta = await busqueda_productos(e.target.value);
//      console.log(respuesta);
                    if (respuesta.error == false) {
                        tabla_init = await ui_pedido_productos_complementos(respuesta.depurar);
                        console.log(tabla_init);
                        if (tabla_init) {
                            handleProductSelect2();
                        }
                    } else {
                        if (tabla_init) {
                            tabla_init.search(e.target.value).draw();
                        }
                    }
                }
            }, 1000);
        });
    }

// Handle product select
    const handleProductSelect = () => {
// Define variables
        const checkboxes = table.querySelectorAll('#kt_ecommerce_edit_order_product_table [type="checkbox"]');
        const target = document.getElementById('kt_ecommerce_edit_order_selected_products');
        // Loop through all checked products
        checkboxes.forEach(checkbox => {
            console.log(checkbox);
            if (checkbox.checked == true) {
                // Select parent row element
                const parent = checkbox.closest('tr');
                // Clone parent element as variable
                const product = parent.querySelector('[data-kt-ecommerce-edit-order-filter="product"]').cloneNode(true);
                // Create inner wrapper
                const innerWrapper = document.createElement('div');
                //Crear input id producto
                const inputProduct = document.createElement('input');
                inputProduct.classList.add("producto-selected");
                inputProduct.classList.add('d-none');
                // Store inner content
                const innerContent = product.innerHTML;
                // Add & remove classes on parent wrapper
                const wrapperClassesAdd = ['col', 'my-2'];
                const wrapperClassesRemove = ['d-flex', 'align-items-center'];
                // Define additional classes
                const additionalClasses = ['border', 'border-dashed', 'rounded', 'p-3', 'bg-body'];
                // Update parent wrapper classes
                product.classList.remove(...wrapperClassesRemove);
                product.classList.add(...wrapperClassesAdd);
                // Remove parent default content
                product.innerHTML = '';
                // Update inner wrapper classes
                innerWrapper.classList.add(...wrapperClassesRemove);
                innerWrapper.classList.add(...additionalClasses);
                // Apply stored inner content into new inner wrapper
                innerWrapper.innerHTML = innerContent;
                // Append new inner wrapper to parent wrapper
                product.appendChild(innerWrapper);
                // Get product id
                const productId = product.getAttribute('data-kt-ecommerce-edit-order-id');
                //Set product id

                inputProduct.name = "productos";
                inputProduct.value = productId;
                inputProduct.setAttribute("data-codigo-barras", product.getAttribute("data-codigo-barras"));
                inputProduct.setAttribute("data-codigo-interno", product.getAttribute("data-codigo-interno"));
                inputProduct.setAttribute("data-codigo-proveedor", product.getAttribute("data-codigo-proveedor"));
                inputProduct.setAttribute("data-producto-precio", product.getAttribute("data-producto-precio"));
                inputProduct.setAttribute("data-producto-iva", product.getAttribute("data-producto-iva"));
                inputProduct.setAttribute("data-producto-descuento", product.getAttribute("data-producto-descuento"));
                inputProduct.setAttribute("data-producto-nombre", product.getAttribute("data-producto-nombre"));
                inputProduct.setAttribute("data-tipo-item", product.getAttribute("data-tipo-item"));
                //dialer
                const div_dialer = document.createElement('div');
                div_dialer.classList.add("input-group");
                div_dialer.classList.add("w-md-200px");
                div_dialer.setAttribute("data-kt-dialer", "true");
                div_dialer.setAttribute("data-kt-dialer-step", "1");
                //button
                const button_dialer_decrease = document.createElement('button');
                button_dialer_decrease.classList.add("btn");
                button_dialer_decrease.classList.add("btn-icon");
                button_dialer_decrease.classList.add("btn-outline");
                button_dialer_decrease.classList.add("btn-active-color-primary");
                button_dialer_decrease.setAttribute("type", "button");
                button_dialer_decrease.setAttribute("data-kt-dialer-control", "decrease");
                //i increase
                const i_dialer_decrease = document.createElement('i');
                i_dialer_decrease.classList.add("bi");
                i_dialer_decrease.classList.add("bi-dash");
                i_dialer_decrease.classList.add("fs-1");
                //i
                button_dialer_decrease.appendChild(i_dialer_decrease);
                //button
                //input
                const input_dialer = document.createElement('input');
                input_dialer.classList.add("form-control");
                input_dialer.setAttribute("type", "text");
                input_dialer.setAttribute("readonly", "readonly");
                input_dialer.setAttribute("placeholder", "cantidad");
                input_dialer.setAttribute("value", product.getAttribute("data-input-cantidad"));
                input_dialer.setAttribute("data-kt-dialer-control", "input");
                input_dialer.setAttribute("id", "pedido-cantidad-" + productId);
                input_dialer.setAttribute("data-input", "cantidad");
                input_dialer.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                input_dialer.addEventListener('change', e => {
//                    detectEmpty();
                });
                //input
                //button
                const button_dialer_increase = document.createElement('button');
                button_dialer_increase.classList.add("btn");
                button_dialer_increase.classList.add("btn-icon");
                button_dialer_increase.classList.add("btn-outline");
                button_dialer_increase.classList.add("btn-active-color-primary");
                button_dialer_increase.setAttribute("type", "button");
                button_dialer_increase.setAttribute("data-kt-dialer-control", "increase");
                //i increase
                const i_dialer_increase = document.createElement('i');
                i_dialer_increase.classList.add("bi");
                i_dialer_increase.classList.add("bi-plus");
                i_dialer_increase.classList.add("fs-1");
                //i
                button_dialer_increase.appendChild(i_dialer_increase);
                //button
//                div_dialer.appendChild(button_dialer_decrease);
//                div_dialer.appendChild(input_dialer);
//                div_dialer.appendChild(button_dialer_increase);
//                //dialer
//                product.appendChild(inputProduct);
//                product.appendChild(div_dialer);
                if (checkbox.checked == true) {
                    // Add product to selected product wrapper
                    target.appendChild(product);
                    KTDialer.createInstances();
                } else {
                    // Remove product from selected product wrapper
                    const selectedProduct = target.querySelector('[data-kt-ecommerce-edit-order-id="' + productId + '"]');
                    if (selectedProduct) {
                        target.removeChild(selectedProduct);
                    }
                }

                // Trigger empty message logic
//                detectEmpty();
            }
            checkbox.addEventListener('change', e => {
                // Select parent row element
                const parent = checkbox.closest('tr');
                // Clone parent element as variable
                const product = parent.querySelector('[data-kt-ecommerce-edit-order-filter="product"]').cloneNode(true);
                // Create inner wrapper
                const innerWrapper = document.createElement('div');
                //Crear input id producto
                const inputProduct = document.createElement('input');
                inputProduct.classList.add("producto-selected");
                inputProduct.classList.add('d-none');
                // Store inner content
                const innerContent = product.innerHTML;
                // Add & remove classes on parent wrapper
                const wrapperClassesAdd = ['col', 'my-2'];
                const wrapperClassesRemove = ['d-flex', 'align-items-center'];
                // Define additional classes
                const additionalClasses = ['border', 'border-dashed', 'rounded', 'p-3', 'bg-body'];
                // Update parent wrapper classes
                product.classList.remove(...wrapperClassesRemove);
                product.classList.add(...wrapperClassesAdd);
                // Remove parent default content
                product.innerHTML = '';
                // Update inner wrapper classes
                innerWrapper.classList.add(...wrapperClassesRemove);
                innerWrapper.classList.add(...additionalClasses);
                // Apply stored inner content into new inner wrapper
                innerWrapper.innerHTML = innerContent;
                // Append new inner wrapper to parent wrapper
                product.appendChild(innerWrapper);
                // Get product id
                const productId = product.getAttribute('data-kt-ecommerce-edit-order-id');
                //Set product id

                inputProduct.name = "productos";
                inputProduct.value = productId;
                inputProduct.setAttribute("data-codigo-barras", product.getAttribute("data-codigo-barras"));
                inputProduct.setAttribute("data-codigo-interno", product.getAttribute("data-codigo-interno"));
                inputProduct.setAttribute("data-codigo-proveedor", product.getAttribute("data-codigo-proveedor"));
                inputProduct.setAttribute("data-producto-precio", product.getAttribute("data-producto-precio"));
                inputProduct.setAttribute("data-producto-iva", product.getAttribute("data-producto-iva"));
                inputProduct.setAttribute("data-producto-descuento", product.getAttribute("data-producto-descuento"));
                inputProduct.setAttribute("data-producto-nombre", product.getAttribute("data-producto-nombre"));
                inputProduct.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                inputProduct.setAttribute("data-tipo-item", product.getAttribute("data-tipo-item"));
                //dialer
                const div_dialer = document.createElement('div');
                div_dialer.classList.add("input-group");
                div_dialer.classList.add("w-md-200px");
                div_dialer.setAttribute("data-kt-dialer", "true");
                div_dialer.setAttribute("data-kt-dialer-step", "1");
                //button
                const button_dialer_decrease = document.createElement('button');
                button_dialer_decrease.classList.add("btn");
                button_dialer_decrease.classList.add("btn-icon");
                button_dialer_decrease.classList.add("btn-outline");
                button_dialer_decrease.classList.add("btn-active-color-primary");
                button_dialer_decrease.setAttribute("type", "button");
                button_dialer_decrease.setAttribute("data-kt-dialer-control", "decrease");
                //i increase
                const i_dialer_decrease = document.createElement('i');
                i_dialer_decrease.classList.add("bi");
                i_dialer_decrease.classList.add("bi-dash");
                i_dialer_decrease.classList.add("fs-1");
                //i
                button_dialer_decrease.appendChild(i_dialer_decrease);
                //button
                //input
                const input_dialer = document.createElement('input');
                input_dialer.classList.add("form-control");
                input_dialer.setAttribute("type", "text");
                input_dialer.setAttribute("readonly", "readonly");
                input_dialer.setAttribute("placeholder", "cantidad");
                input_dialer.setAttribute("value", "1");
                input_dialer.setAttribute("data-kt-dialer-control", "input");
                input_dialer.setAttribute("id", "pedido-cantidad-" + productId);
                input_dialer.setAttribute("data-input", "cantidad");
                input_dialer.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                input_dialer.addEventListener('change', e => {
//                    detectEmpty();
                });
                //input
                //button
                const button_dialer_increase = document.createElement('button');
                button_dialer_increase.classList.add("btn");
                button_dialer_increase.classList.add("btn-icon");
                button_dialer_increase.classList.add("btn-outline");
                button_dialer_increase.classList.add("btn-active-color-primary");
                button_dialer_increase.setAttribute("type", "button");
                button_dialer_increase.setAttribute("data-kt-dialer-control", "increase");
                //i increase
                const i_dialer_increase = document.createElement('i');
                i_dialer_increase.classList.add("bi");
                i_dialer_increase.classList.add("bi-plus");
                i_dialer_increase.classList.add("fs-1");
                //i
                button_dialer_increase.appendChild(i_dialer_increase);
                //button
//                div_dialer.appendChild(button_dialer_decrease);
//                div_dialer.appendChild(input_dialer);
//                div_dialer.appendChild(button_dialer_increase);
                //dialer
                product.appendChild(inputProduct);
                product.appendChild(div_dialer);
                if (e.target.checked) {
                    // Add product to selected product wrapper
                    target.appendChild(product);
                    KTDialer.createInstances();
                } else {
                    // Remove product from selected product wrapper
                    const selectedProduct = target.querySelector('[data-kt-ecommerce-edit-order-id="' + productId + '"]');
                    if (selectedProduct) {
                        target.removeChild(selectedProduct);
                    }
                }

                // Trigger empty message logic
//                detectEmpty();
            });
        });
    }

    const handleProductSelect2 = () => {
// Define variables
        const checkboxes = table2.querySelectorAll('#kt_ecommerce_edit_order_product_table_complementos [type="checkbox"]');
        const target = document.getElementById('kt_ecommerce_edit_order_selected_products_complementos');
        // Loop through all checked products
        console.log(checkboxes);
        checkboxes.forEach(checkbox => {
            console.log(checkbox);
            if (checkbox.checked == true) {
                // Select parent row element
                const parent = checkbox.closest('tr');
                // Clone parent element as variable
                const product = parent.querySelector('[data-kt-ecommerce-edit-order-filter="product"]').cloneNode(true);
                // Create inner wrapper
                const innerWrapper = document.createElement('div');
                //Crear input id producto
                const inputProduct = document.createElement('input');
                inputProduct.classList.add("producto-selected");
                inputProduct.classList.add('d-none');
                // Store inner content
                const innerContent = product.innerHTML;
                // Add & remove classes on parent wrapper
                const wrapperClassesAdd = ['col', 'my-2'];
                const wrapperClassesRemove = ['d-flex', 'align-items-center'];
                // Define additional classes
                const additionalClasses = ['border', 'border-dashed', 'rounded', 'p-3', 'bg-body'];
                // Update parent wrapper classes
                product.classList.remove(...wrapperClassesRemove);
                product.classList.add(...wrapperClassesAdd);
                // Remove parent default content
                product.innerHTML = '';
                // Update inner wrapper classes
                innerWrapper.classList.add(...wrapperClassesRemove);
                innerWrapper.classList.add(...additionalClasses);
                // Apply stored inner content into new inner wrapper
                innerWrapper.innerHTML = innerContent;
                // Append new inner wrapper to parent wrapper
                product.appendChild(innerWrapper);
                // Get product id
                const productId = product.getAttribute('data-kt-ecommerce-edit-order-id');
                //Set product id

                inputProduct.name = "productos";
                inputProduct.value = productId;
                inputProduct.setAttribute("data-codigo-barras", product.getAttribute("data-codigo-barras"));
                inputProduct.setAttribute("data-codigo-interno", product.getAttribute("data-codigo-interno"));
                inputProduct.setAttribute("data-codigo-proveedor", product.getAttribute("data-codigo-proveedor"));
                inputProduct.setAttribute("data-producto-precio", product.getAttribute("data-producto-precio"));
                inputProduct.setAttribute("data-producto-iva", product.getAttribute("data-producto-iva"));
                inputProduct.setAttribute("data-producto-descuento", product.getAttribute("data-producto-descuento"));
                inputProduct.setAttribute("data-producto-nombre", product.getAttribute("data-producto-nombre"));
                inputProduct.setAttribute("data-tipo-item", product.getAttribute("data-tipo-item"));
                //dialer
                const div_dialer = document.createElement('div');
                div_dialer.classList.add("input-group");
                div_dialer.classList.add("w-md-200px");
                div_dialer.setAttribute("data-kt-dialer", "true");
                div_dialer.setAttribute("data-kt-dialer-step", "1");
                //button
                const button_dialer_decrease = document.createElement('button');
                button_dialer_decrease.classList.add("btn");
                button_dialer_decrease.classList.add("btn-icon");
                button_dialer_decrease.classList.add("btn-outline");
                button_dialer_decrease.classList.add("btn-active-color-primary");
                button_dialer_decrease.setAttribute("type", "button");
                button_dialer_decrease.setAttribute("data-kt-dialer-control", "decrease");
                //i increase
                const i_dialer_decrease = document.createElement('i');
                i_dialer_decrease.classList.add("bi");
                i_dialer_decrease.classList.add("bi-dash");
                i_dialer_decrease.classList.add("fs-1");
                //i
                button_dialer_decrease.appendChild(i_dialer_decrease);
                //button
                //input
                const input_dialer = document.createElement('input');
                input_dialer.classList.add("form-control");
                input_dialer.setAttribute("type", "text");
                input_dialer.setAttribute("readonly", "readonly");
                input_dialer.setAttribute("placeholder", "cantidad");
                input_dialer.setAttribute("value", product.getAttribute("data-input-cantidad"));
                input_dialer.setAttribute("data-kt-dialer-control", "input");
                input_dialer.setAttribute("id", "pedido-cantidad-" + productId);
                input_dialer.setAttribute("data-input", "cantidad");
                input_dialer.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                input_dialer.addEventListener('change', e => {
//                    detectEmpty();
                });
                //input
                //button
                const button_dialer_increase = document.createElement('button');
                button_dialer_increase.classList.add("btn");
                button_dialer_increase.classList.add("btn-icon");
                button_dialer_increase.classList.add("btn-outline");
                button_dialer_increase.classList.add("btn-active-color-primary");
                button_dialer_increase.setAttribute("type", "button");
                button_dialer_increase.setAttribute("data-kt-dialer-control", "increase");
                //i increase
                const i_dialer_increase = document.createElement('i');
                i_dialer_increase.classList.add("bi");
                i_dialer_increase.classList.add("bi-plus");
                i_dialer_increase.classList.add("fs-1");
                //i
                button_dialer_increase.appendChild(i_dialer_increase);
                //button
//                div_dialer.appendChild(button_dialer_decrease);
//                div_dialer.appendChild(input_dialer);
//                div_dialer.appendChild(button_dialer_increase);
//                //dialer
//                product.appendChild(inputProduct);
//                product.appendChild(div_dialer);
                if (checkbox.checked == true) {
                    // Add product to selected product wrapper
                    target.appendChild(product);
                    KTDialer.createInstances();
                } else {
                    // Remove product from selected product wrapper
                    const selectedProduct = target.querySelector('[data-kt-ecommerce-edit-order-id="' + productId + '"]');
                    if (selectedProduct) {
                        target.removeChild(selectedProduct);
                    }
                }

                // Trigger empty message logic
//                detectEmpty();
            }
            checkbox.addEventListener('change', e => {
                // Select parent row element
                const parent = checkbox.closest('tr');
                // Clone parent element as variable
                const product = parent.querySelector('[data-kt-ecommerce-edit-order-filter="product"]').cloneNode(true);
                // Create inner wrapper
                const innerWrapper = document.createElement('div');
                //Crear input id producto
                const inputProduct = document.createElement('input');
                inputProduct.classList.add("producto-selected");
                inputProduct.classList.add('d-none');
                // Store inner content
                const innerContent = product.innerHTML;
                // Add & remove classes on parent wrapper
                const wrapperClassesAdd = ['col', 'my-2'];
                const wrapperClassesRemove = ['d-flex', 'align-items-center'];
                // Define additional classes
                const additionalClasses = ['border', 'border-dashed', 'rounded', 'p-3', 'bg-body'];
                // Update parent wrapper classes
                product.classList.remove(...wrapperClassesRemove);
                product.classList.add(...wrapperClassesAdd);
                // Remove parent default content
                product.innerHTML = '';
                // Update inner wrapper classes
                innerWrapper.classList.add(...wrapperClassesRemove);
                innerWrapper.classList.add(...additionalClasses);
                // Apply stored inner content into new inner wrapper
                innerWrapper.innerHTML = innerContent;
                // Append new inner wrapper to parent wrapper
                product.appendChild(innerWrapper);
                // Get product id
                const productId = product.getAttribute('data-kt-ecommerce-edit-order-id');
                //Set product id

                inputProduct.name = "productos";
                inputProduct.value = productId;
                inputProduct.setAttribute("data-codigo-barras", product.getAttribute("data-codigo-barras"));
                inputProduct.setAttribute("data-codigo-interno", product.getAttribute("data-codigo-interno"));
                inputProduct.setAttribute("data-codigo-proveedor", product.getAttribute("data-codigo-proveedor"));
                inputProduct.setAttribute("data-producto-precio", product.getAttribute("data-producto-precio"));
                inputProduct.setAttribute("data-producto-iva", product.getAttribute("data-producto-iva"));
                inputProduct.setAttribute("data-producto-descuento", product.getAttribute("data-producto-descuento"));
                inputProduct.setAttribute("data-producto-nombre", product.getAttribute("data-producto-nombre"));
                inputProduct.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                inputProduct.setAttribute("data-tipo-item", product.getAttribute("data-tipo-item"));
                //dialer
                const div_dialer = document.createElement('div');
                div_dialer.classList.add("input-group");
                div_dialer.classList.add("w-md-200px");
                div_dialer.setAttribute("data-kt-dialer", "true");
                div_dialer.setAttribute("data-kt-dialer-step", "1");
                //button
                const button_dialer_decrease = document.createElement('button');
                button_dialer_decrease.classList.add("btn");
                button_dialer_decrease.classList.add("btn-icon");
                button_dialer_decrease.classList.add("btn-outline");
                button_dialer_decrease.classList.add("btn-active-color-primary");
                button_dialer_decrease.setAttribute("type", "button");
                button_dialer_decrease.setAttribute("data-kt-dialer-control", "decrease");
                //i increase
                const i_dialer_decrease = document.createElement('i');
                i_dialer_decrease.classList.add("bi");
                i_dialer_decrease.classList.add("bi-dash");
                i_dialer_decrease.classList.add("fs-1");
                //i
                button_dialer_decrease.appendChild(i_dialer_decrease);
                //button
                //input
                const input_dialer = document.createElement('input');
                input_dialer.classList.add("form-control");
                input_dialer.setAttribute("type", "text");
                input_dialer.setAttribute("readonly", "readonly");
                input_dialer.setAttribute("placeholder", "cantidad");
                input_dialer.setAttribute("value", "1");
                input_dialer.setAttribute("data-kt-dialer-control", "input");
                input_dialer.setAttribute("id", "pedido-cantidad-" + productId);
                input_dialer.setAttribute("data-input", "cantidad");
                input_dialer.setAttribute("data-input-cantidad", product.getAttribute("data-input-cantidad"));
                input_dialer.addEventListener('change', e => {
//                    detectEmpty();
                });
                //input
                //button
                const button_dialer_increase = document.createElement('button');
                button_dialer_increase.classList.add("btn");
                button_dialer_increase.classList.add("btn-icon");
                button_dialer_increase.classList.add("btn-outline");
                button_dialer_increase.classList.add("btn-active-color-primary");
                button_dialer_increase.setAttribute("type", "button");
                button_dialer_increase.setAttribute("data-kt-dialer-control", "increase");
                //i increase
                const i_dialer_increase = document.createElement('i');
                i_dialer_increase.classList.add("bi");
                i_dialer_increase.classList.add("bi-plus");
                i_dialer_increase.classList.add("fs-1");
                //i
                button_dialer_increase.appendChild(i_dialer_increase);
                //button
//                div_dialer.appendChild(button_dialer_decrease);
//                div_dialer.appendChild(input_dialer);
//                div_dialer.appendChild(button_dialer_increase);
                //dialer
                product.appendChild(inputProduct);
                product.appendChild(div_dialer);
                if (e.target.checked) {
                    // Add product to selected product wrapper
                    target.appendChild(product);
                    KTDialer.createInstances();
                } else {
                    // Remove product from selected product wrapper
                    const selectedProduct = target.querySelector('[data-kt-ecommerce-edit-order-id="' + productId + '"]');
                    if (selectedProduct) {
                        target.removeChild(selectedProduct);
                    }
                }

                // Trigger empty message logic
//                detectEmpty();
            });
        });
    }

// Init condition select2
    const initConditionsSelect2 = () => {
// Tnit new repeating condition types
        const allConditionTypes = document.querySelectorAll('[data-kt-ecommerce-catalog-add-product="product_option"]');
        allConditionTypes.forEach(type => {
            if ($(type).hasClass("select2-hidden-accessible")) {
                return;
            } else {
                $(type).select2({
                    minimumResultsForSearch: -1
                });
            }
        });
    }

// Init DropzoneJS --- more info:
    const initDropzone = () => {
        var myDropzone = new Dropzone("#kt_ecommerce_add_product_media", {
            url: "https://keenthemes.com/scripts/void.php", // Set the url for your upload script location
            paramName: "file", // The name that will be used to transfer the file
            maxFiles: 10,
            maxFilesize: 10, // MB
            addRemoveLinks: true,
            accept: function (file, done) {
                if (file.name == "wow.jpg") {
                    done("Naha, you don't.");
                } else {
                    done();
                }
            }
        });
    }

// Handle discount options
    const handleDiscount = () => {
        const discountOptions = document.querySelectorAll('input[name="discount_option"]');
        const percentageEl = document.getElementById('kt_ecommerce_add_product_discount_percentage');
        const fixedEl = document.getElementById('kt_ecommerce_add_product_discount_fixed');
        discountOptions.forEach(option => {
            option.addEventListener('change', e => {
                const value = e.target.value;
                switch (value) {
                    case '2':
                    {
                        percentageEl.classList.remove('d-none');
                        fixedEl.classList.add('d-none');
                        break;
                    }
                    case '3':
                    {
                        percentageEl.classList.add('d-none');
                        fixedEl.classList.remove('d-none');
                        break;
                    }
                    default:
                    {
                        percentageEl.classList.add('d-none');
                        fixedEl.classList.add('d-none');
                        break;
                    }
                }
            });
        });
    }

// Shipping option handler
    const handleShipping = () => {
        const shippingOption = document.getElementById('kt_ecommerce_add_product_shipping_checkbox');
        const shippingForm = document.getElementById('kt_ecommerce_add_product_shipping');
        shippingOption.addEventListener('change', e => {
            const value = e.target.checked;
            if (value) {
                shippingForm.classList.remove('d-none');
            } else {
                shippingForm.classList.add('d-none');
            }
        });
    }

// Category status handler
    const handleStatus = () => {
        const target = document.getElementById('kt_ecommerce_add_product_status');
        const select = document.getElementById('kt_ecommerce_add_product_status_select');
        const statusClasses = ['bg-success', 'bg-warning', 'bg-danger'];
        $(select).on('change', function (e) {
            const value = e.target.value;
            switch (value) {
                case "published":
                {
                    target.classList.remove(...statusClasses);
                    target.classList.add('bg-success');
                    hideDatepicker();
                    break;
                }
                case "scheduled":
                {
                    target.classList.remove(...statusClasses);
                    target.classList.add('bg-warning');
                    showDatepicker();
                    break;
                }
                case "inactive":
                {
                    target.classList.remove(...statusClasses);
                    target.classList.add('bg-danger');
                    hideDatepicker();
                    break;
                }
                case "draft":
                {
                    target.classList.remove(...statusClasses);
                    target.classList.add('bg-primary');
                    hideDatepicker();
                    break;
                }
                default:
                    break;
            }
        });
        // Handle datepicker
        const datepicker = document.getElementById('kt_ecommerce_add_product_status_datepicker');
        // Init flatpickr --- more info: https://flatpickr.js.org/
        $('#kt_ecommerce_add_product_status_datepicker').flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });
        const showDatepicker = () => {
            datepicker.parentNode.classList.remove('d-none');
        }

        const hideDatepicker = () => {
            datepicker.parentNode.classList.add('d-none');
        }
    }

// Condition type handler
    const handleConditions = () => {
        const allConditions = document.querySelectorAll('[name="method"][type="radio"]');
        const conditionMatch = document.querySelector('[data-kt-ecommerce-catalog-add-category="auto-options"]');
        allConditions.forEach(radio => {
            radio.addEventListener('change', e => {
                if (e.target.value === '1') {
                    conditionMatch.classList.remove('d-none');
                } else {
                    conditionMatch.classList.add('d-none');
                }
            });
        })
    }

// Submit form handler
    const handleSubmit = () => {
// Define variables
        let validator;
        // Get elements
        const form = document.getElementById('kt_ecommerce_add_product_form');
        const submitButton = document.getElementById('kt_ecommerce_add_product_submit');
        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validator = FormValidation.formValidation(
                form,
                {
                    fields: {
                        'producto_nombre': {
                            validators: {
                                notEmpty: {
                                    message: 'Nombre del producto es requerido'
                                }
                            }
                        },
                        'codigo_interno': {
                            validators: {
                                notEmpty: {
                                    message: 'Código de interno es requerido'
                                }
                            }
                        },
                        'codigo_unico': {
                            validators: {
                                notEmpty: {
                                    message: 'Código único es requerido'
                                }
                            }
                        },
                        'precio_base': {
                            validators: {
                                notEmpty: {
                                    message: 'Precio base es requerido'
                                }
                            }
                        }
                    },
                    plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                            rowSelector: '.fv-row',
                            eleInvalidClass: '',
                            eleValidClass: ''
                        })
                    }
                }
        );
        // Handle submit button
        submitButton.addEventListener('click', e => {
            e.preventDefault();
            // Validate form before submit
            if (validator) {
                validator.validate().then(async function (status) {
                    console.log('validated!');
                    if (status == 'Valid') {
                        submitButton.disabled = true;
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        let informacion = obtener_informacion();
                        let response = actualizar(informacion);
                        if (response.error == false) {
                            if ($("#avatar")[0].files.length > 0) {
                                let imgPortada = document.getElementById('avatar').files[0];
                                let formSubirPortada = new FormData();
                                formSubirPortada.append('portada', imgPortada);
                                formSubirPortada.append('id_producto', informacion.id_producto);
                                formSubirPortada.append('tipo_imagen', 'portada');
                                let respuesta = await actualizar_producto_portada(formSubirPortada);
                                if (respuesta.error == false) {
                                    Swal.fire({
                                        text: respuesta.mensaje,
                                        icon: "success",
                                        buttonsStyling: false,
                                        confirmButtonText: "Perfecto!",
                                        customClass: {
                                            confirmButton: "btn btn-primary"
                                        }
                                    }).then(function (result) {
                                        if (result.isConfirmed) {
                                            // Enable submit button after loading
                                            submitButton.disabled = false;
                                        }
                                    });
                                }
                            } else {

                                submitButton.removeAttribute('data-kt-indicator');
                                Swal.fire({
                                    text: response.mensaje,
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Perfecto!",
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                }).then(function (result) {
                                    if (result.isConfirmed) {
                                        // Enable submit button after loading
                                        submitButton.disabled = false;
                                        // Redirect to customers list page
//                  window.location = form.getAttribute("data-kt-redirect");
                                    }
                                });
                            }
                        } else {
                            Swal.fire({
                                text: response.mensaje,
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            });
                        }




                        // Disable submit button whilst loading
//            submitButton.disabled = true;
//
//            setTimeout(function () {
//              submitButton.removeAttribute('data-kt-indicator');
//
//              Swal.fire({
//                text: "Form has been successfully submitted!",
//                icon: "success",
//                buttonsStyling: false,
//                confirmButtonText: "Ok, got it!",
//                customClass: {
//                  confirmButton: "btn btn-primary"
//                }
//              }).then(function (result) {
//                if (result.isConfirmed) {
//                  // Enable submit button after loading
//                  submitButton.disabled = false;
//
//                  // Redirect to customers list page
////                  window.location = form.getAttribute("data-kt-redirect");
//                }
//              });
//            }, 2000);
                    } else {
                        Swal.fire({
                            html: "Sorry, looks like there are some errors detected, please try again. <br/><br/>Please note that there may be errors in the <strong>General</strong> or <strong>Advanced</strong> tabs",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });
                    }
                });
            }
        })
    }

// Public methods
    return {
        init: function () {
// Init forms
            initQuill();
//            initTagify();
//            initSlider();
            initFormRepeater();
            initDropzone();
            initConditionsSelect2();
            handleSearchDatatable();
            handleSearchDatatable2();
            handleProductSelect();
            handleProductSelect2();
            // Handle forms
            handleStatus();
            handleConditions();
            handleDiscount();
            handleShipping();
            handleSubmit();
        }
    };
}();
// On document ready
KTUtil.onDOMContentLoaded(function () {

    let response = producto_a_editar();
    if (response['error'] == false) {
        ui_datos_formulario(response.depurar.info.depurar);
//    ui_proveedores_producto(consulta.depurar.proveedores.depurar);
    }
    let taglist = [];
    if (response.depurar.tags.error == false) {
        generar_lista_tags(response.depurar.tags.depurar);
    }

    if (response.depurar.atributos.error == false) {
        ui_atributos(response.depurar.atributos.depurar);
    }

    if (response.depurar.variantes.error == false) {
        ui_variantes(response.depurar.variantes.depurar);
    }

    let imagenes = response.depurar.imagenes;
    if (imagenes.error == false) {
        ui_imagenes(imagenes.depurar);
    }

    let proveedores_p = [];
    if (response.depurar.proveedores.error == false) {
        proveedores_p = proveedores_producto(response.depurar.proveedores.depurar);
    }

    let proveedores = proveedor_listar();
    proveedores = JSON.parse(proveedores);
    if (proveedores.error == false) {
        ui_select_proveedores(proveedores.depurar, proveedores_p);
    }
    if (response.depurar.sugeridos.error == false) {
        ui_pedido_productos_registrados(response.depurar.sugeridos.depurar);
    }

    let categorias_p = [];
    if (response.depurar.categorias.error == false) {
        categorias_p = categorias_producto(response.depurar.categorias.depurar);
    }
    let categorias = consultar_categorias();
    if (categorias.error == false) {
        ui_select_categorias(categorias.depurar, categorias_p);
    }

    let marcas_p = [];
    if (response.depurar.marcas.error == false) {
        marcas_p = marcas_producto(response.depurar.marcas.depurar);
    }
    let marcas = consultar_marcas();
    if (marcas.error == false) {
        ui_select_marcas(marcas.depurar, marcas_p);
    }

    if (response.depurar.compra_venta.error == false) {
        COMPRA_VENTA_P = response.depurar.compra_venta;
    }

    let consulta_unidades_compra_venta = consultar_unidades_compra_venta();
    if (consulta_unidades_compra_venta.error == false) {
        UNIDADES_COMPRA_VENTA = consulta_unidades_compra_venta.depurar;
        ui_unidades_compra();
    }

    initTagify();
    KTAppEcommerceSaveProduct.init();
});
async function busqueda_productos(value) {
    let data = {
        busqueda: value
    }
    let respuesta = await consulta_busqueda(data);
    return respuesta;
}
async function ui_pedido_productos_registrados(data) {
    let codigo_productos = "";
    data.map(function (producto) {
        codigo_productos += `
        <tr class="odd">
          <!--begin::Checkbox-->
          <td>
              <div class="form-check form-check-sm form-check-custom form-check-solid">
                  <input class="form-check-input input-check-producto" type="checkbox" checked="checked">
              </div>
          </td>
          <!--end::Checkbox-->
          <!--begin::Product=-->
          <td>
              <div class="d-flex align-items-center" data-kt-ecommerce-edit-order-filter="product" data-producto-descuento="0.00" data-tipo-item="${producto.tipo_item}" data-producto-iva="${producto.precio_base * 0.16}" data-producto-precio="${producto.precio_base}" data-producto-nombre="${producto.nombre}" data-kt-ecommerce-edit-order-id="${producto.tipo_item == 'paquete' ? producto.id_paquete : producto.id_producto}" data-codigo-interno="${producto.codigo_interno}" data-codigo-proveedor="${producto.sku}" data-codigo-barras="${producto.codigo_barras}" data-input-cantidad="${producto.cantidad ? producto.cantidad : 1}">
                  <!--begin::Thumbnail-->
                  <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="symbol symbol-50px">
                      <span class="symbol-label" style="background-image:url('${producto.url_imagen}');"></span>
                  </a>
                  <!--end::Thumbnail-->
                  <div class="ms-5">
                      <!--begin::Title-->
                      <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="text-gray-800 text-hover-primary fs-5 fw-bold">${producto.nombre}</a>
                      <!--end::Title-->
                      <!--begin::Price-->
                      <div class="fw-semibold fs-7">Price: $
                          <span data-kt-ecommerce-edit-order-filter="price">${producto.precio_base}</span></div>
                      <!--end::Price-->
                      <!--begin::SKU-->
                      <div class="text-muted fs-7">SKU: ${producto.sku}</div>
                      <!--end::SKU-->
                  </div>
              </div>
          </td>
          <!--end::Product=-->
          <!--begin::Qty=-->
          <td class="text-end pe-5" data-order="9">
              <!--<span class="badge badge-light-warning">Low stock</span>-->
              <span class="fw-bold text-warning ms-3">${producto.existencia}</span>
          </td>
          <!--end::Qty=-->
      </tr>
    `;
    });
    $("#pedido_productos").html(codigo_productos);
}
async function ui_pedido_productos(data) {
    let codigo_productos = "";
    data.map(function (producto) {
        codigo_productos += `
        <tr class="odd">
          <!--begin::Checkbox-->
          <td>
              <div class="form-check form-check-sm form-check-custom form-check-solid">
                  <input class="form-check-input input-check-producto" type="checkbox" ${producto.id_pedido ? 'checked="checked"' : ''}>
              </div>
          </td>
          <!--end::Checkbox-->
          <!--begin::Product=-->
          <td>
              <div class="d-flex align-items-center" data-kt-ecommerce-edit-order-filter="product" data-producto-descuento="0.00" data-tipo-item="${producto.tipo_item}" data-producto-iva="${producto.precio_base * 0.16}" data-producto-precio="${producto.precio_base}" data-producto-nombre="${producto.nombre}" data-kt-ecommerce-edit-order-id="${producto.tipo_item == 'paquete' ? producto.id_paquete : producto.id_producto}" data-codigo-interno="${producto.codigo_interno}" data-codigo-proveedor="${producto.sku}" data-codigo-barras="${producto.codigo_barras}" data-input-cantidad="${producto.cantidad ? producto.cantidad : 1}">
                  <!--begin::Thumbnail-->
                  <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="symbol symbol-50px">
                      <span class="symbol-label" style="background-image:url('${producto.url_imagen}');"></span>
                  </a>
                  <!--end::Thumbnail-->
                  <div class="ms-5">
                      <!--begin::Title-->
                      <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="text-gray-800 text-hover-primary fs-5 fw-bold">${producto.nombre}</a>
                      <!--end::Title-->
                      <!--begin::Price-->
                      <div class="fw-semibold fs-7">Price: $
                          <span data-kt-ecommerce-edit-order-filter="price">${producto.precio_base}</span></div>
                      <!--end::Price-->
                      <!--begin::SKU-->
                      <div class="text-muted fs-7">SKU: ${producto.sku}</div>
                      <!--end::SKU-->
                  </div>
              </div>
          </td>
          <!--end::Product=-->
          <!--begin::Qty=-->
          <td class="text-end pe-5" data-order="9">
              <!--<span class="badge badge-light-warning">Low stock</span>-->
              <span class="fw-bold text-warning ms-3">${producto.existencia}</span>
          </td>
          <!--end::Qty=-->
      </tr>
    `;
    });
//    $("#pedido_productos").html(codigo_productos);
    return await init_dataTables('kt_ecommerce_edit_order_product_table', codigo_productos);
}

async function ui_pedido_productos_complementos(data) {
    let codigo_productos = "";
    data.map(function (producto) {
        codigo_productos += `
        <tr class="odd">
          <!--begin::Checkbox-->
          <td>
              <div class="form-check form-check-sm form-check-custom form-check-solid">
                  <input class="form-check-input input-check-producto" type="checkbox" ${producto.id_pedido ? 'checked="checked"' : ''}>
              </div>
          </td>
          <!--end::Checkbox-->
          <!--begin::Product=-->
          <td>
              <div class="d-flex align-items-center" data-kt-ecommerce-edit-order-filter="product" data-producto-descuento="0.00" data-tipo-item="${producto.tipo_item}" data-producto-iva="${producto.precio_base * 0.16}" data-producto-precio="${producto.precio_base}" data-producto-nombre="${producto.nombre}" data-kt-ecommerce-edit-order-id="${producto.tipo_item == 'paquete' ? producto.id_paquete : producto.id_producto}" data-codigo-interno="${producto.codigo_interno}" data-codigo-proveedor="${producto.sku}" data-codigo-barras="${producto.codigo_barras}" data-input-cantidad="${producto.cantidad ? producto.cantidad : 1}">
                  <!--begin::Thumbnail-->
                  <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="symbol symbol-50px">
                      <span class="symbol-label" style="background-image:url('${producto.url_imagen}');"></span>
                  </a>
                  <!--end::Thumbnail-->
                  <div class="ms-5">
                      <!--begin::Title-->
                      <a href="../../demo1/dist/apps/ecommerce/catalog/edit-product.html" class="text-gray-800 text-hover-primary fs-5 fw-bold">${producto.nombre}</a>
                      <!--end::Title-->
                      <!--begin::Price-->
                      <div class="fw-semibold fs-7">Price: $
                          <span data-kt-ecommerce-edit-order-filter="price">${producto.precio_base}</span></div>
                      <!--end::Price-->
                      <!--begin::SKU-->
                      <div class="text-muted fs-7">SKU: ${producto.sku}</div>
                      <!--end::SKU-->
                  </div>
              </div>
          </td>
          <!--end::Product=-->
          <!--begin::Qty=-->
          <td class="text-end pe-5" data-order="9">
              <!--<span class="badge badge-light-warning">Low stock</span>-->
              <span class="fw-bold text-warning ms-3">${producto.existencia}</span>
          </td>
          <!--end::Qty=-->
      </tr>
    `;
    });
//    $("#pedido_productos").html(codigo_productos);
    return await init_dataTables2('kt_ecommerce_edit_order_product_table_complementos', codigo_productos);
}

function init_dataTables(identificador, codigo_productos) {
    $('#' + identificador).DataTable().destroy();
    $("#pedido_productos").html(codigo_productos);
    // Init datatable --- more info on datatables: https://datatables.net/manual/
    let table = document.querySelector('#' + identificador);
    let datatable = $(table).DataTable({
        'order': [],
        "scrollY": "400px",
        "scrollCollapse": true,
        "paging": false,
        "info": false,
        'columnDefs': [
            {orderable: false, targets: 0}, // Disable ordering on column 0 (checkbox)
        ]
    });
    return datatable;
}

function init_dataTables2(identificador, codigo_productos) {
    $('#' + identificador).DataTable().destroy();
    $("#pedido_productos_complementos").html(codigo_productos);
    // Init datatable --- more info on datatables: https://datatables.net/manual/
    let table = document.querySelector('#' + identificador);
    let datatable = $(table).DataTable({
        'order': [],
        "scrollY": "400px",
        "scrollCollapse": true,
        "paging": false,
        "info": false,
        'columnDefs': [
            {orderable: false, targets: 0}, // Disable ordering on column 0 (checkbox)
        ]
    });
    return datatable;
}

function generar_lista_tags(lista) {
    let array_tags = [];
    lista.map(function (tag) {
        array_tags.push(tag['tag']);
    });
    let string_tags = array_tags.toString();
    $("#kt_ecommerce_add_product_tags").val(string_tags);
//    return array_tags;
}

// Init tagify
function initTagify(tagList = null) {
    console.log(tagList);
    // Define all elements for tagify
    const elements = [
        '#kt_ecommerce_add_product_category',
        '#kt_ecommerce_add_product_tags'
    ];
    // Loop all elements
    elements.forEach(element => {
        // Get tagify element
        const tagify = document.querySelector(element);
        // Break if element not found
        if (!tagify) {
            return;
        }

        // Init tagify --- more info: https://yaireo.github.io/tagify/
        new Tagify(tagify, {
            whitelist: tagList != null ? tagList : [],
            dropdown: {
                maxItems: 20, // <- mixumum allowed rendered suggestions
                classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                enabled: 0, // <- show suggestions on focus
                closeOnSelect: false    // <- do not hide the suggestions dropdown once an item has been selected
            }
        });
    });
}

function ui_imagenes(imagenes) {
//style background-url
//$(".image-input-wrapper")
    let img;
    imagenes.map(function (imagen) {
        if (imagen.tipo_imagen == "portada") {
//      img = elemento_imagen({"src": imagen.url_imagen});
            ui_pluggin_imagen(imagen.url_imagen);
        } else {

        }
    })

}

function ui_unidades_compra() {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar Unidad compra"});
    $("#unidad_compra").html("");
    $("#unidad_compra").append(opcion);
    Object.entries(UNIDADES_COMPRA_VENTA).forEach((unidad_compra) => {
        if (COMPRA_VENTA_P.error == false) {
            if (COMPRA_VENTA_P.depurar.id_unidad_compra == unidad_compra[1].id_unidad_compra) {
                opcion = crear_opcion({value: unidad_compra[1].id_unidad_compra, text: unidad_compra[1].abreviatura + "-" + unidad_compra[1].unidad_compra, selected: "selected"});
                $("#unidad_compra").append(opcion);
            } else {
                opcion = crear_opcion({value: unidad_compra[1].id_unidad_compra, text: unidad_compra[1].abreviatura + "-" + unidad_compra[1].unidad_compra});
                $("#unidad_compra").append(opcion);
            }
        } else {
            opcion = crear_opcion({value: unidad_compra[1].id_unidad_compra, text: unidad_compra[1].abreviatura + "-" + unidad_compra[1].unidad_compra});
            $("#unidad_compra").append(opcion);
        }

    });
    if (COMPRA_VENTA_P.error == false) {
        $("#factor_compra").val(COMPRA_VENTA_P.depurar.factor);
    }
    $("#unidad_compra").trigger("change");
//    UNIDADES_COMPRA_VENTA.map(function (unidad_compra) {
//        opcion = crear_opcion({value: unidad_compra.id_unidad_compra, text: unidad_compra.abreviatura + " " + unidad_compra.unidad_compra});
//        $("#unidad_compra").append(opcion);
//    });
}

function ui_unidades_venta() {
    let opcion = "";
    let id_unidad_compra = $('#unidad_compra option:selected').val();
    $("#unidad_venta").html("");
    Object.entries(UNIDADES_COMPRA_VENTA).forEach((unidad_compra) => {
        if (unidad_compra[1].id_unidad_compra == id_unidad_compra) {
            Object.entries(unidad_compra[1].unidades_venta).forEach((unidad_venta) => {
                if (COMPRA_VENTA_P.error == false) {
                    if (COMPRA_VENTA_P.depurar.id_unidad_venta == unidad_venta[1].id_unidad_venta) {
                        opcion = crear_opcion({value: unidad_venta[0], text: unidad_venta[1].abreviatura + "-" + unidad_venta[1].unidad_venta, selected: "selected"});
                        $("#unidad_venta").append(opcion);
                    } else {
                        opcion = crear_opcion({value: unidad_venta[0], text: unidad_venta[1].abreviatura + "-" + unidad_venta[1].unidad_venta});
                        $("#unidad_venta").append(opcion);
                    }
                } else {
                    opcion = crear_opcion({value: unidad_venta[0], text: unidad_venta[1].abreviatura + "-" + unidad_venta[1].unidad_venta});
                    $("#unidad_venta").append(opcion);
                }

            });
        }

    });
    ui_factor_compra(id_unidad_compra);
}

function ui_factor_compra(id_unidad_compra) {
    if (id_unidad_compra == 2) {
        $("#factor_compra").removeAttr("disabled");
        $("#solo_en_punto_de_venta")[0].checked = true;
    } else {
        $("#factor_compra").val(1);
        $("#factor_compra").attr("disabled", "disabled");
        $("#solo_en_punto_de_venta")[0].checked = false;
    }
}

function elemento_imagen(json) {
//  $('<img />', { 'src': '/imagenes/yoda.png', 'id': 'miImagen', 'class':'miClase' }).appendTo('#contenedor');
    let img = $('<img />', json)[0];
    return img;
}

function ui_pluggin_imagen(elemento_imagen) {
    console.log(elemento_imagen);
//  var imageInput = new KTImageInput(elemento_imagen);
    $(".image-input-wrapper").css("background-image", `url('${elemento_imagen}')`);
    $(".image-input").addClass("image-input-changed");
    $(".image-input").removeClass("image-input-empty");
    $(".image-input").removeAttr("data-kt-image-input");
}

function ui_select_categorias(data_categorias, data_categorias_productos) {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar categoria"});
    $("#select_categorias").html("");
    $("#select_categorias").append(opcion);
    data_categorias.map(function (categoria) {
        if (data_categorias_productos.includes(categoria.id_categoria)) {
            opcion = crear_opcion({value: categoria.id_categoria, text: categoria.categoria, selected: true});
        } else {
            opcion = crear_opcion({value: categoria.id_categoria, text: categoria.categoria});
        }
        $("#select_categorias").append(opcion);
    });
}

function ui_select_marcas(data_categorias, data_categorias_productos) {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar marca"});
    $("#select_marcas").html("");
    $("#select_marcas").append(opcion);
    data_categorias.map(function (categoria) {
        if (data_categorias_productos.includes(categoria.id_marca)) {
            opcion = crear_opcion({value: categoria.id_marca, text: categoria.marca, selected: true});
        } else {
            opcion = crear_opcion({value: categoria.id_marca, text: categoria.marca});
        }
        $("#select_marcas").append(opcion);
    });
}

function ui_select_proveedores(data_proveedores, data_proveedores_productos) {
    let opcion_select = "";
    let opcion = crear_opcion({value: -1, text: "Seleccionar proveedor"});
    $("#select_proveedores").html("");
    $("#select_proveedores").append(opcion);
    data_proveedores.map(function (proveedor) {
        if (data_proveedores_productos.includes(proveedor.id_proveedor)) {
            opcion = crear_opcion({value: proveedor.id_proveedor, text: proveedor.proveedor, selected: true});
        } else {
            opcion = crear_opcion({value: proveedor.id_proveedor, text: proveedor.proveedor});
        }
        $("#select_proveedores").append(opcion);
    });
}

function crear_opcion(opciones) {
    let opcion = $('<option>', opciones)[0];
    return opcion;
}

function producto_a_editar() {
    let id_producto = obtener_id_producto();
    let data = {
        id_usuario_mayoreo: id_producto
    }
    let consulta = JSON.parse(consultar_producto_editar(data));
    console.log(consulta);
    return consulta;
}

function consultar_producto_editar(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/usuario/consultar_informacion_usuario", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = datos;
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function categorias_producto(categorias) {
    let values = [];
    categorias.map(function (categoria) {
        values.push(categoria.id_categoria);
    });
//  console.log(values);
//  console.log(JSON.stringify(values));
//  $('#select_proveedores').select2({data: data});
//  selected_dinamic_values('#select_proveedores', values);
    return values;
}

function marcas_producto(categorias) {
    let values = [];
    categorias.map(function (categoria) {
        values.push(categoria.id_marca);
    });
//  console.log(values);
//  console.log(JSON.stringify(values));
//  $('#select_proveedores').select2({data: data});
//  selected_dinamic_values('#select_proveedores', values);
    return values;
}

function proveedores_producto(proveedores) {
    let values = [];
    proveedores.map(function (proveedor) {
        values.push(proveedor.id_proveedor);
    });
//  console.log(values);
//  console.log(JSON.stringify(values));
//  $('#select_proveedores').select2({data: data});
//  selected_dinamic_values('#select_proveedores', values);
    return values;
}

function selected_dinamic_values(identificador, values) {
    setTimeout(function () {
        $(identificador).val(values).trigger('change');
    }, 2000);
}

function ui_datos_formulario(datos) {
    $("#producto_nombre").val(datos.nombre);
    $("#kt_ecommerce_add_product_description").html(datos.descripcion);
    $("#siempre_disponible")[0].checked = datos.siempre_disponible == 0 ? false : true;
    $("#precio_base").val(datos.precio_base);
    $("#codigo_unico").val(datos.sku);
    $("#codigo_interno").val(datos.codigo_interno);
    $("#codigo_barras").val(datos.codigo_barras);
    $("#cantidad").val(datos.existencia);
    $("#minima_existencia").val(datos.minima_existencia);
    $("#maxima_existencia").val(datos.maxima_existencia);
}

function obtener_id_producto() {
    let router = url_router();
    let id_producto = router.parametros[0];
    return id_producto;
}

function form_data() {
    let form = document.getElementById("kt_ecommerce_add_product_form");
    let data = new FormData(form);
    return data;
}

function obtener_informacion() {

//informacion Personal
    let usuario_nombre = $("#usuario_nombre").val();
    let usuario_alias = $("#usuario_alias").val();
    let usuario_apellido_materno = $("#usuario_apellido_materno").val();
    let usuario_apellido_paterno = $("#usuario_apellido_paterno").val();
    let usuario_correo = $("#usuario_correo").val();
    let usuario_telefono = $("#usuario_telefono").val();
    let usuario_celular = $("#usuario_celular").val();
    //Informacion Negocio
    let negocio_nombre = $("#negocio_nombre").val();
    let negocio_calle = $("#usuario_celular").val();
    let negocio_numero_exterior = $("#negocio_numero_exterior").val();
    let negocio_numero_interior = $("#negocio_numero_interior").val();
    let negocio_codigo_postal = $("#negocio_codigo_postal").val();
    let negocio_colonia = $("#negocio_colonia").val();
    let negocio_telefono_fijo = $("#negocio_telefono_fijo").val();
    let negocio_celular = $("#negocio_celular").val();
    let negocio_tipo = $("#negocio_tipo option:selected").val();
    //Informacion de Envío
    let envio_costo = $("#envio_costo").val();
    let envio_calle = $("#envio_calle").val();
    let envio_numero_exterior = $("#envio_numero_exterior").val();
    let envio_numero_interior = $("#envio_numero_interior").val();
    let envio_colonia = $("#envio_colonia").val();
    let envio_codigo_postal = $("#envio_codigo_postal").val();
    //Informacion Fiscal
    let fiscal_razon_social = $("#fiscal_razon_social").val();
    let fiscal_rfc = $("#fiscal_rfc").val();
    let fiscal_regimen = $("#fiscal_regimen").val();
    let fiscal_usocfdi = $("#fiscal_usocfdi").val();
    let fiscal_codigo_postal = $("#fiscal_codigo_postal").val();

    let informacion_personal = {
        usuario_nombre,
        usuario_alias,
        usuario_apellido_materno,
        usuario_apellido_paterno,
        usuario_correo,
        usuario_telefono,
        usuario_celular
    };
    let informacion_negocio = {
        negocio_nombre,
        negocio_calle,
        negocio_numero_exterior,
        negocio_numero_interior,
        negocio_codigo_postal,
        negocio_colonia,
        negocio_telefono_fijo,
        negocio_celular,
        negocio_tipo
    };
    let informacion_envio = {
        envio_costo,
        envio_calle,
        envio_numero_exterior,
        envio_numero_interior,
        envio_colonia,
        envio_codigo_postal
    };
    let informacion_fiscal = {
        usuario_nombre,
        usuario_alias,
        usuario_apellido_materno,
        usuario_apellido_paterno,
        usuario_correo,
        usuario_telefono,
        usuario_celular
    };
    let informacion = {
        id_producto: id_producto,
        producto_nombre: producto_nombre,
        descripcion: descripcion,
        siempre_disponible: siempre_disponible,
        precio_base: precio_base,
        codigo_unico: codigo_unico,
        codigo_barras: codigo_barras,
        cantidad: cantidad,
        minima_existencia: minima_existencia,
        maxima_existencia: maxima_existencia,
        codigo_interno: codigo_interno,
        proveedores: proveedores,
        categorias: categorias,
        marcas: marcas,
        tags: tags,
        atributos: atributos,
        variantes: variantes,
        id_variante: id_variante,
        productos_sugeridos: productos_sugeridos,
        compra_venta
    };
    return informacion;
}

function obtener_productos_sugeridos() {
    let productos = [], subtotal = 0, iva = 0, cantidad = 0, precio = 0;
    if ($(".producto-selected")) {
        $(".producto-selected").each(function (item) {
//              "codigo_barras": "2rwef",
//              "codigo_interno": "dfgdf",
//              "codigo_proveedor": "dfgd",
//              "cantidad": 1,
//              "precio": 1950,
//              "descuento": 0.00,
//              "iva": 312.00,
//              "subtotal": 1950,
//              "producto": "Jaula para conejo amsterdam III",


            productos.push({
                id_producto: this.value
            })
        });
    }

    return productos;
}

function ui_atributos(lista_atributos) {
    var tipo_atributo = "", valor_atributo = "", unidad_medida_atributo = "", descripcion_atributo = "";
    lista_atributos.map(function (atributo) {
        tipo_atributo = atributo['tipo_atributo'];
        valor_atributo = atributo['valor_atributo'];
        unidad_medida_atributo = atributo['unidad_medida_atributo'];
        descripcion_atributo = atributo['descripcion_atributo'];
        $("#tbody_atributos").append(`<tr class="row_atributo">
                                    <td class="p-3 tipo_atributo">
                                        ${tipo_atributo}
                                    </td>
                                    <td class="p-3 valor_atributo">
                                        ${valor_atributo}
                                    </td>
                                    <td class="p-3 unidad_medida_atributo">
                                        ${unidad_medida_atributo}
                                    </td>
                                    <td class="p-3 descripcion_atributo">
                                        ${descripcion_atributo}
                                    </td>
                                    <td class="p-3">
                                        <p style="cursor:pointer;" onclick="eliminar_atributo(this);">
                                            Eliminar
                                        </p>
                                    </td>
                                </tr>`);
    });
}

function ui_variantes(lista_atributos) {
    var tipo_atributo = "", valor_atributo = "", unidad_medida_atributo = "", descripcion_atributo = "";
    lista_atributos.map(function (atributo) {
        id_variante = atributo['id_producto'];
        principal = atributo['principal'];
        id_registro_variante = atributo['id_variante'];
        VARIANTE_REGISTRO = id_registro_variante;
        $("#tbody_variantes").append(`<tr class="row_variante">
                                    <td class="p-3 id_variante">
                                        ${id_variante}
                                    </td>
                                    <td class="p-3 principal">
                                        ${principal}
                                    </td>
                                    <td class="p-3">
                                        <p style="cursor:pointer;" onclick="eliminar_variante(this);">
                                            Eliminar
                                        </p>
                                    </td>
                                </tr>`);
    });
}

function agregar_atributo() {

    var tipo_atributo = $("#tipo_atributo").val();
    var valor_atributo = $("#valor_atributo").val();
    var unidad_medida_atributo = $("#unidad_medida_atributo").val();
    var descripcion_atributo = $("#descripcion_atributo").val();
    $("#tbody_atributos").append(`<tr class="row_atributo">
                                    <td class="p-3 tipo_atributo">
                                        ${tipo_atributo}
                                    </td>
                                    <td class="p-3 valor_atributo">
                                        ${valor_atributo}
                                    </td>
                                    <td class="p-3 unidad_medida_atributo">
                                        ${unidad_medida_atributo}
                                    </td>
                                    <td class="p-3 descripcion_atributo">
                                        ${descripcion_atributo}
                                    </td>
                                    <td class="p-3">
                                        <p style="cursor:pointer;" onclick="eliminar_atributo(this);">
                                            Eliminar
                                        </p>
                                    </td>
                                </tr>`);
    $("#tipo_atributo").val('');
    $("#valor_atributo").val('');
    $("#unidad_medida_atributo").val('');
    $("#descripcion_atributo").val('');
}

function agregar_variante() {

    var id_variante = $("#id_variante").val();
    var principal = $("#principal").val();
    $("#tbody_variantes").append(`<tr class="row_variante">
                                    <td class="p-3 id_variante">
                                        ${id_variante}
                                    </td>
                                    <td class="p-3 principal">
                                        ${principal}
                                    </td>
                                    <td class="p-3">
                                        <p style="cursor:pointer;" onclick="eliminar_variante(this);">
                                            Eliminar
                                        </p>
                                    </td>
                                </tr>`);
    $("#id_variante").val('');
    $("#principal").val('');
}

function obtener_variantes() {
    var array_atributos = [], id_variante = "", principal = "", atributo = "";
    $(".row_variante").each(function () {
//        console.log($(this).find('.tipo_atributo'));
        id_variante = $(this).find('.id_variante') ? $($(this).find('.id_variante')).text().trim() : '';
        principal = $(this).find('.principal') ? $($(this).find('.principal')).text().trim() : '';
//        console.log(tipo_atributo);
        atributo = {
            id_variante,
            principal
        }
        array_atributos.push(atributo);
    });
//    console.log(array_atributos);
    return array_atributos.length > 0 ? array_atributos : null;
}

function obtener_atributos() {
    var array_atributos = [], tipo_atributo = "", valor_atributo = "", unidad_medida = "", descripcion = "", atributo = "";
    $(".row_atributo").each(function () {
//        console.log($(this).find('.tipo_atributo'));
        tipo_atributo = $(this).find('.tipo_atributo') ? $($(this).find('.tipo_atributo')).text().trim() : '';
        valor_atributo = $(this).find('.valor_atributo') ? $($(this).find('.valor_atributo')).text().trim() : '';
        unidad_medida = $(this).find('.unidad_medida_atributo') ? $($(this).find('.unidad_medida_atributo')).text().trim() : '';
        descripcion = $(this).find('.descripcion_atributo') ? $($(this).find('.descripcion_atributo')).text().trim() : '';
//        console.log(tipo_atributo);
        atributo = {
            tipo_atributo,
            valor_atributo,
            unidad_medida,
            descripcion
        }
        array_atributos.push(atributo);
    });
//    console.log(array_atributos);
    return array_atributos.length > 0 ? array_atributos : null;
}

function eliminar_atributo(elemento) {
    console.log(elemento);
    $(elemento).parent().parent().remove();
}

function eliminar_variante(elemento) {
    console.log(elemento);
    $(elemento).parent().parent().remove();
}

function obtener_lista_tags() {
    var respuesta = null;
    if ($('#kt_ecommerce_add_product_tags').val()) {
        var TagValues = JSON.parse($('#kt_ecommerce_add_product_tags').val());
        console.log(TagValues);
        var TagArray = []

        for (let i = 0; i < TagValues.length; i++) {
            console.log(TagValues[i]);
            TagArray.push(TagValues[i].value)
        }
        console.log(TagArray);
        respuesta = TagArray;
    }
    return respuesta;
}

$.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name] !== undefined)
        {
            if (!o[this.name].push)
            {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else
        {
            o[this.name] = this.value || '';
        }
    });
    return o;
};
function obtenerDatosFormulario(idFormulario) {
    let datos = jQuery(idFormulario).serializeArray();
    let a_datos = new Object();
    for (i = 0; i < datos.length; i++) {
        let llave = datos[i]["name"];
        let valor = datos[i]["value"];
        a_datos[llave] = valor;
    }
    return a_datos;
}