// frontend/src/main.js
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import "./assets/main.css"; // si usas CSS global
import "leaflet/dist/leaflet.css"; // Importa estilos de Leaflet

const app = createApp(App);
app.use(router);
app.mount("#app");
