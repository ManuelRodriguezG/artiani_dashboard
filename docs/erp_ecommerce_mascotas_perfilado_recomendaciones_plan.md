# ERP Ecommerce - Perfilado de mascotas y recomendaciones

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-30  
Estado: plan vivo para evolucionar ecommerce de catalogo a ecosistema especializado por mascota.

## Objetivo

Construir una capa de conocimiento de mascotas para que Artiani no sea un ecommerce generico de productos/categorias, sino una experiencia guiada por la mascota real del cliente.

La meta es que el cliente pueda decir:

- que mascota tiene;
- que etapa de vida, tamano, raza o rasgos la describen;
- que necesidades tiene;
- que restricciones o condiciones importan;
- que objetivo busca resolver.

Con eso el ecommerce podra recomendar productos compatibles, explicar por que aplican y permitir que el cliente explore mas si quiere.

## Decision conceptual

No modelar mascotas como una copia literal de programacion orientada a objetos.

Si conviene tomar la idea base:

- una especie funciona como plantilla;
- cada plantilla tiene atributos configurables;
- cada mascota registrada es una instancia con valores;
- las necesidades funcionan como comportamientos/contextos;
- los productos declaran compatibilidad con especies, atributos, necesidades y restricciones.

Nombre operativo recomendado:

```text
Taxonomia viva de mascotas
```

Tambien puede entenderse como:

```text
Perfil de mascota + reglas de compatibilidad + recomendaciones
```

## Principios

- El cliente debe sentirse identificado con su mascota, no forzado a navegar categorias frias.
- El frontend debe poder empezar simple: elegir especie y necesidad.
- El ERP debe permitir crecer hacia perfiles completos sin rehacer la arquitectura.
- Las recomendaciones no deben prometer diagnostico medico.
- Salud, enfermedad, dieta terapeutica o tratamientos deben manejarse como orientacion comercial prudente y, cuando aplique, recomendar consulta veterinaria.
- El producto sigue siendo del Catalogo ERP; la capa de mascotas solo agrega compatibilidad y contexto.

## Modelo mental

### Plantilla de especie

Ejemplos:

- perro;
- gato;
- pez;
- ave;
- reptil;
- roedor;
- otra.

Cada especie tiene atributos relevantes distintos.

Ejemplo Perro:

- etapa de vida;
- tamano;
- raza;
- tipo de pelo;
- nivel de actividad;
- sensibilidad digestiva;
- condicion de piel/pelo;
- vive interior/exterior;
- objetivo actual.

Ejemplo Pez:

- tipo de agua;
- especie o familia;
- tamano de acuario;
- etapa;
- tipo de alimento;
- necesidad de filtracion;
- temperatura;
- comportamiento.

Ejemplo Ave:

- especie/familia;
- tamano;
- tipo de jaula;
- alimentacion;
- enriquecimiento;
- higiene;
- etapa.

## Capas de informacion

### 1. Especie

Es la entrada principal.

Valores iniciales:

```text
perro
gato
pez
ave
reptil
roedor
otra
```

### 2. Necesidad

Representa lo que el cliente quiere resolver.

Valores iniciales:

```text
alimento
premio
higiene
salud
paseo
habitat
juguete
estetica
entrenamiento
viaje
descanso
suplemento
```

### 3. Atributos de mascota

Son datos que describen a la mascota.

Tipos de atributo:

- seleccion unica;
- seleccion multiple;
- numero;
- rango;
- texto controlado;
- booleano;
- fecha/edad calculada.

Ejemplos transversales:

- nombre;
- especie;
- fecha de nacimiento o edad aproximada;
- etapa de vida;
- sexo;
- esterilizado;
- peso;
- tamano;
- nivel de actividad;
- condiciones conocidas;
- preferencias;
- restricciones.

### 4. Atributos por especie

No todos los atributos aplican a todas las especies.

Perro:

- raza;
- tamano: mini, pequeno, mediano, grande, gigante;
- etapa: cachorro, adulto, senior;
- tipo de pelo: corto, largo, rizado, doble capa, sin pelo;
- actividad: baja, media, alta;
- sensibilidad: digestiva, piel, articulaciones, control peso;
- objetivo: alimentacion diaria, entrenamiento, higiene, paseo, descanso.

Gato:

- etapa: gatito, adulto, senior;
- pelo: corto, largo, sin pelo;
- estilo de vida: interior, exterior, mixto;
- esterilizado;
- sensibilidad: urinaria, bolas de pelo, digestiva, control peso;
- objetivo: alimento, arena, rascador, juguete, higiene.

Pez:

- agua: dulce, salada;
- tipo: tropical, agua fria, betta, goldfish, ciclido, comunitario;
- tamano acuario;
- necesidad: alimento, filtracion, oxigenacion, iluminacion, decoracion, tratamiento;
- temperatura;
- etapa/tamano.

Ave:

- tipo: canario, perico, loro, ninfa, gallina/ave corral, otra;
- tamano;
- jaula/habitat;
- alimentacion;
- enriquecimiento;
- higiene.

Reptil:

- tipo: tortuga, iguana, gecko, serpiente, camaleon, otro;
- habitat: acuatico, semiacuatico, terrestre, arboricola;
- temperatura/humedad;
- UVB/calefaccion;
- alimentacion.

Roedor:

- tipo: hamster, cuyo, conejo, rata, raton, chinchilla, otro;
- tamano;
- habitat;
- alimento;
- sustrato/cama;
- enriquecimiento/dental.

## Relacion producto - mascota

Cada publicacion o SKU ecommerce debe poder declarar compatibilidad.

Campos conceptuales:

- especies compatibles;
- necesidades que cubre;
- etapa de vida compatible;
- tamanos compatibles;
- razas o familias recomendadas si aplica;
- atributos requeridos;
- atributos no recomendados;
- restricciones;
- advertencias;
- nivel de confianza.

Ejemplo:

```json
{
  "id_sku": 123,
  "especies": ["perro"],
  "necesidades": ["alimento"],
  "etapas": ["adulto"],
  "tamanos": ["mediano", "grande"],
  "restricciones": ["no_recomendado_cachorro"],
  "beneficios": ["digestivo", "piel_pelo"],
  "explicacion": "Recomendado para perros adultos medianos o grandes con actividad media."
}
```

## Recomendacion

La recomendacion debe responder:

```text
Para esta mascota, que productos aplican y por que.
```

No basta con filtrar productos. Debe dar contexto.

Ejemplo frontend:

```text
Para Luna, perro adulto mediano:
- Alimento adulto mediano: coincide con etapa y tamano.
- Shampoo piel sensible: coincide con pelo corto y sensibilidad de piel.
- Correa reforzada: coincide con tamano y actividad alta.
```

## Niveles de madurez

### Fase 1 - Navegacion guiada sin registro

Frontend pregunta:

- Que mascota tienes?
- Que necesitas?

Consume:

```http
GET /ecommercePublico/taxonomia_mascotas
GET /ecommercePublico/catalogo?mascota=perro&necesidad=alimento
```

ERP actual ya soporta esto de forma basica.

### Fase 2 - Perfil temporal en frontend

El cliente configura una mascota sin crear cuenta:

- especie;
- etapa;
- tamano;
- necesidad;
- algunos atributos clave.

El frontend guarda temporalmente en navegador y pide recomendaciones read-only.

Endpoint futuro:

```http
POST /ecommercePublico/mascota_recomendaciones_preview
```

Contrato:

- no registra cliente;
- no guarda mascota;
- no diagnostica;
- devuelve productos compatibles y razones.

### Fase 3 - Configuracion ERP de atributos

Panel interno para que Artiani configure:

- especies;
- atributos por especie;
- valores permitidos;
- necesidades;
- relaciones atributo-necesidad;
- textos de ayuda;
- reglas de compatibilidad.

Esto permite que frontend no hardcodee las preguntas.

### Fase 4 - Mascotas registradas por cliente

Cliente crea cuenta o se vincula a CRM.

Puede registrar:

- nombre de mascota;
- especie;
- raza/tipo;
- edad;
- peso/tamano;
- atributos;
- preferencias;
- restricciones;
- historial de recomendaciones/cotizaciones.

Requiere privacidad, consentimiento y contrato CRM.

### Fase 5 - Recomendaciones avanzadas

Con historial:

- productos vistos;
- productos cotizados;
- compras reales desde POS/Pedidos;
- busquedas sin resultado;
- recordatorios;
- recompensas;
- campanas por especie/necesidad.

## Modelo de datos propuesto

No aplicar DDL todavia. Primero validar operativamente el modelo.

Tablas futuras sugeridas:

- `erp_mascotas_especies`
- `erp_mascotas_atributos`
- `erp_mascotas_atributos_valores`
- `erp_mascotas_especie_atributos`
- `erp_mascotas_necesidades`
- `erp_mascotas_reglas_compatibilidad`
- `erp_ecommerce_producto_mascota_compatibilidad`
- `crm_clientes_mascotas`
- `crm_clientes_mascotas_atributos`

Separacion recomendada:

- `erp_mascotas_*`: catalogo/configuracion de conocimiento.
- `erp_ecommerce_producto_mascota_compatibilidad`: relacion producto-publicacion con mascota/necesidad.
- `crm_clientes_mascotas*`: mascotas reales de clientes.

## API futura para frontend

### Taxonomia enriquecida

```http
GET /ecommercePublico/taxonomia_mascotas
```

Debe evolucionar para devolver:

- especies;
- necesidades;
- atributos por especie;
- opciones;
- orden de preguntas;
- textos de ayuda;
- defaults.

### Preview de recomendacion

```http
POST /ecommercePublico/mascota_recomendaciones_preview
```

Body:

```json
{
  "mascota": {
    "especie": "perro",
    "etapa": "adulto",
    "tamano": "mediano",
    "pelo": "corto",
    "actividad": "media",
    "sensibilidades": ["piel"]
  },
  "necesidad": "higiene",
  "limite": 24
}
```

Respuesta:

```json
{
  "error": false,
  "tipo": "success",
  "depurar": {
    "items": [
      {
        "id_publicacion": 10,
        "slug": "shampoo-piel-sensible",
        "score": 82,
        "razones": [
          "Compatible con perro",
          "Aplica para higiene",
          "Recomendado para sensibilidad de piel"
        ],
        "advertencias": []
      }
    ],
    "guardrails": {
      "no_diagnostica": true,
      "no_sustituye_veterinario": true
    }
  }
}
```

### Mascotas registradas

Fase posterior:

```http
GET /clientes/mascotas
POST /clientes/mascotas
PUT /clientes/mascotas/{id}
```

Estos no deben ser publicos anonimos sin autenticacion/consentimiento.

## Panel ERP recomendado

Nueva seccion futura:

```text
Ecommerce > Mascotas
```

Pestanas:

- Especies;
- Atributos;
- Necesidades;
- Compatibilidad de productos;
- Reglas de recomendacion;
- Vista previa.

Flujo operativo:

1. Crear especie o editar especie.
2. Definir atributos aplicables.
3. Definir valores permitidos.
4. Definir necesidades relevantes.
5. Relacionar productos/publicaciones con compatibilidades.
6. Probar una mascota ejemplo.
7. Enviar contrato al frontend.

## UX frontend sugerida

Primera experiencia:

```text
Compra para tu mascota
```

Pasos:

1. Elige mascota: perro, gato, pez, ave, reptil, roedor.
2. Elige que necesitas: alimento, higiene, habitat, juguete, salud, paseo.
3. Responde 1 a 3 preguntas utiles segun especie.
4. Ver productos recomendados.
5. Ver por que se recomiendan.
6. Permitir "ver todo" sin encerrar al cliente.

Reglas UX:

- No hacer formularios largos al inicio.
- Preguntar solo lo que cambia la recomendacion.
- Permitir saltar preguntas.
- Explicar la recomendacion en lenguaje simple.
- Guardar perfil temporal local si no hay login.
- No usar lenguaje medico absoluto.

## Ejemplo de flujo

Cliente:

```text
Tengo un perro adulto mediano con piel sensible.
```

Sistema:

- especie=perro;
- etapa=adulto;
- tamano=mediano;
- condicion=piel_sensible.

Recomendaciones:

- alimento adulto mediano si necesidad=alimento;
- shampoo piel sensible si necesidad=higiene;
- premios hipoalergenicos si necesidad=premio;
- cepillo para pelo corto/largo segun atributo pelo.

## Riesgos

- Sobrecomplicar la captura y frenar la compra.
- Inventar atributos que nadie usa para recomendar.
- Prometer soluciones medicas.
- Duplicar categorias de catalogo con taxonomia de mascotas.
- Relacionar productos masivamente sin revision.

## Siguiente paso recomendado

1. Crear endpoint read-only enriquecido de `taxonomia_mascotas` con estructura de especies, atributos y preguntas iniciales.
2. Crear documento/fixture para frontend con el flujo de seleccion de mascota.
3. Crear panel ERP simple para administrar taxonomia, primero sin DDL o usando defaults en codigo/documento.
4. Despues de validar UX con frontend, preparar DDL de `erp_mascotas_*`.
5. Luego relacionar publicaciones ecommerce con compatibilidades.

## Mensaje para frontend

```text
La experiencia de ecommerce debe partir de la mascota, no solo de categorias.

Fase inicial:
- selector de especie;
- selector de necesidad;
- 1 a 3 preguntas contextuales por especie;
- listado recomendado con explicacion;
- opcion de ver todo.

No crear cuenta todavia como requisito.
No prometer diagnosticos.
No bloquear la compra si el cliente no completa el perfil.

El ERP preparara una API de taxonomia enriquecida para que las preguntas y opciones sean configurables.
```
