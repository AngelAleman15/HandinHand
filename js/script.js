// Creacion dinámica de los productos
const products = [
    {
        img: 'img/QCYH2PRO.png',
        title: "Auriculares QCY H2 PRO",
        description: "Auriculares inalámbricos de alta calidad con cancelación de ruido y sonido estéreo premium."
    },
    {
        img: 'img/Smartphone.jpeg',
        title: "Smartphone Android",
        description: "Teléfono en excelente estado, 128GB de almacenamiento, cámara de 48MP y batería de larga duración."
    },
    {
        img: 'img/LaptopGaming.jpeg',
        title: "Laptop Gaming",
        description: "Laptop para gaming con tarjeta gráfica dedicada, 16GB RAM y procesador de última generación."
    },
    {
        img: 'img/Smartwatch.jpeg',
        title: "Smartwatch Deportivo",
        description: "Reloj inteligente con GPS, monitor de ritmo cardíaco y resistencia al agua para deportes acuáticos."
    },
    {
        img: 'img/xbox.jpeg',
        title: "Xbox 360",
        description: "Consola de última generación con 5 juegos incluidos, dos controles y todos los cables necesarios."
    },
    {
        img: 'img/Bicicleta.jpeg',
        title: "Bicicleta de Montaña",
        description: "Bicicleta con suspensión delantera, frenos de disco y cuadro de aluminio, ideal para rutas exigentes."
    },
    {
        img: 'img/camara.jpeg',
        title: "Cámara Réflex",
        description: "Cámara profesional con lente intercambiable, perfecta para fotografía y video en alta resolución."
    },
    {
        img: 'img/patin.jpeg',
        title: "Patín Eléctrico",
        description: "Patín eléctrico plegable, batería de larga duración y velocidad máxima de 25 km/h."
    },
    {
        img: 'img/guitarraelectrica.png',
        title: "Guitarra Eléctrica",
        description: "Guitarra eléctrica con amplificador incluido, ideal para principiantes y músicos avanzados."
    },
    {
        img: 'img/coleccion de libros.jpeg',
        title: "Colección de Libros",
        description: "Colección de novelas clásicas y contemporáneas en perfecto estado, ideal para amantes de la lectura."
    }
];

function renderProductsWithoutDescription() {
    const productsContainer = document.querySelector('.products-container');
    productsContainer.innerHTML = '';
    products.forEach(product => {
        productsContainer.innerHTML += `
            <div class="product">
                <img src="${product.img}" alt="${product.title}">
                <h6 class="info">${product.title}</h6>
            </div>
        `;
    });
}

renderProductsWithoutDescription();