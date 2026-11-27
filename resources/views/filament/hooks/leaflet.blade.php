<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>
    .mapa-teselas-oscuras { filter: invert(1) hue-rotate(180deg) brightness(.9) contrast(.9) saturate(.6); }
    .leaflet-container { font-family: inherit; background: #1f1f23; }
</style>
<script>
    window.mapaEntregas = (datos, conIndicaciones = false) => ({
        mapa: null,
        cargando: true,
        error: false,
        resumen: '',
        pasos: [],

        init() {
            if (typeof L === 'undefined') {
                this.cargando = false;
                this.error = true;
                this.resumen = 'No se pudo cargar el mapa.';

                return;
            }

            const oscuro = document.documentElement.classList.contains('dark');
            const mapa = L.map(this.$refs.lienzo, { scrollWheelZoom: false });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                className: oscuro ? 'mapa-teselas-oscuras' : '',
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> · Rutas <a href="https://routing.openstreetmap.de/about.html">FOSSGIS OSRM</a>',
            }).addTo(mapa);

            const puntos = [];

            if (datos.origen) {
                puntos.push([datos.origen.lat, datos.origen.lng]);
                L.marker([datos.origen.lat, datos.origen.lng], { icon: this.icono('L', '#f59e0b'), zIndexOffset: -100 })
                    .bindPopup(`<strong>${this.escapar(datos.origen.titulo)}</strong>`)
                    .addTo(mapa);
            }

            datos.paradas.forEach((parada) => {
                puntos.push([parada.lat, parada.lng]);
                L.marker([parada.lat, parada.lng], { icon: this.icono(parada.orden, parada.color), zIndexOffset: 100 })
                    .bindPopup(`<strong>${this.escapar(parada.titulo)}</strong><br>${this.escapar(parada.detalle)}`)
                    .addTo(mapa);
            });

            if (puntos.length === 1) {
                mapa.setView(puntos[0], 15);
            } else {
                mapa.fitBounds(puntos, { padding: [32, 32], maxZoom: 16 });
            }

            setTimeout(() => mapa.invalidateSize(), 250);
            this.mapa = mapa;

            if (puntos.length > 1) {
                this.trazarPorCalles(puntos);
            } else {
                this.cargando = false;
            }
        },

        async trazarPorCalles(puntos) {
            const servicio = datos.perfil === 'bike' ? 'routed-bike' : 'routed-car';
            const coordenadas = puntos.map(([lat, lng]) => `${lng.toFixed(6)},${lat.toFixed(6)}`).join(';');
            const url = `https://routing.openstreetmap.de/${servicio}/route/v1/driving/${coordenadas}?overview=full&geometries=geojson&steps=${conIndicaciones}`;

            try {
                const controlador = new AbortController();
                const limite = setTimeout(() => controlador.abort(), 10000);
                const respuesta = await fetch(url, { signal: controlador.signal });
                clearTimeout(limite);

                const json = await respuesta.json();

                if (! respuesta.ok || json.code !== 'Ok' || ! json.routes?.length) {
                    throw new Error(json.message || 'Sin ruta');
                }

                if (! this.mapa) {
                    return;
                }

                const ruta = json.routes[0];

                L.geoJSON(ruta.geometry, {
                    style: { color: '#ffffff', weight: 8, opacity: 0.6 },
                    interactive: false,
                }).addTo(this.mapa);

                const linea = L.geoJSON(ruta.geometry, {
                    style: { color: '#2563eb', weight: 5, opacity: 0.9 },
                    interactive: false,
                }).addTo(this.mapa);

                this.mapa.fitBounds(linea.getBounds(), { padding: [32, 32], maxZoom: 16 });

                const vehiculo = datos.perfil === 'bike' ? 'en bicicleta' : 'por calles';
                this.resumen = `${this.kilometros(ruta.distance)} · ${this.minutos(ruta.duration)} ${vehiculo} (estimado, sin tráfico)`;

                if (conIndicaciones) {
                    this.pasos = this.construirPasos(ruta.legs);
                }
            } catch (excepcion) {
                if (this.mapa) {
                    L.polyline(puntos, { color: '#2563eb', weight: 3, opacity: 0.7, dashArray: '6 8' }).addTo(this.mapa);
                }

                this.error = true;
                this.resumen = 'No se pudo calcular el recorrido por calles; se muestra una línea recta referencial.';
            } finally {
                this.cargando = false;
            }
        },

        construirPasos(tramos) {
            const pasos = [];

            tramos.forEach((tramo, indiceTramo) => {
                const destino = datos.origen ? datos.paradas[indiceTramo] : datos.paradas[indiceTramo + 1];

                tramo.steps.forEach((paso) => {
                    const tipo = paso.maneuver.type;

                    if (tipo === 'depart' && indiceTramo > 0) {
                        return;
                    }

                    if (tipo === 'arrive') {
                        pasos.push({
                            icono: '📍',
                            texto: destino ? `Llegas a la parada ${destino.orden}: ${destino.titulo}` : 'Llegas al destino',
                            distancia: '',
                            llegada: true,
                        });

                        return;
                    }

                    pasos.push({
                        icono: this.flecha(paso.maneuver.modifier, tipo),
                        texto: this.instruccion(paso),
                        distancia: paso.distance >= 1 ? this.metros(paso.distance) : '',
                        llegada: false,
                    });
                });
            });

            return pasos;
        },

        instruccion(paso) {
            const calle = paso.name ? paso.name : 'la calle sin nombre';
            const sentido = {
                left: 'a la izquierda',
                right: 'a la derecha',
                'slight left': 'levemente a la izquierda',
                'slight right': 'levemente a la derecha',
                'sharp left': 'cerrado a la izquierda',
                'sharp right': 'cerrado a la derecha',
                straight: 'derecho',
                uturn: 'en U',
            }[paso.maneuver.modifier] ?? '';

            switch (paso.maneuver.type) {
                case 'depart':
                    return `Sal por ${calle}`;
                case 'turn':
                    return `Gira ${sentido} en ${calle}`;
                case 'end of road':
                    return `Al final de la calle, gira ${sentido} en ${calle}`;
                case 'new name':
                    return `Continúa por ${calle}`;
                case 'continue':
                    return paso.maneuver.modifier === 'uturn' ? `Da la vuelta en U por ${calle}` : `Sigue ${sentido} por ${calle}`;
                case 'fork':
                    return `En la bifurcación, mantente ${sentido} hacia ${calle}`;
                case 'merge':
                    return `Incorpórate ${sentido} a ${calle}`;
                case 'on ramp':
                    return `Toma el acceso ${sentido} hacia ${calle}`;
                case 'off ramp':
                    return `Toma la salida ${sentido} hacia ${calle}`;
                case 'roundabout':
                case 'rotary':
                    return paso.maneuver.exit
                        ? `En la rotonda, toma la ${paso.maneuver.exit}ª salida hacia ${calle}`
                        : `Entra a la rotonda y sigue hacia ${calle}`;
                case 'exit roundabout':
                case 'exit rotary':
                    return `Sal de la rotonda hacia ${calle}`;
                default:
                    return `Continúa por ${calle}`;
            }
        },

        flecha(modificador, tipo) {
            if (tipo === 'depart') {
                return '🚩';
            }

            if (tipo === 'roundabout' || tipo === 'rotary') {
                return '⟳';
            }

            return {
                left: '←',
                'sharp left': '↰',
                'slight left': '↖',
                right: '→',
                'sharp right': '↱',
                'slight right': '↗',
                uturn: '↶',
            }[modificador] ?? '↑';
        },

        metros(valor) {
            return valor >= 1000 ? this.kilometros(valor) : `${Math.round(valor / 10) * 10} m`;
        },

        kilometros(valor) {
            return `${(valor / 1000).toFixed(1).replace('.', ',')} km`;
        },

        minutos(segundos) {
            const total = Math.max(1, Math.round(segundos / 60));

            return total >= 60 ? `${Math.floor(total / 60)} h ${total % 60} min` : `${total} min`;
        },

        icono(texto, color) {
            return L.divIcon({
                className: '',
                html: `<span style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:999px;background:${color};color:#fff;font:700 12px/1 system-ui,sans-serif;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)">${this.escapar(texto)}</span>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
                popupAnchor: [0, -16],
            });
        },

        escapar(texto) {
            return String(texto ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        },

        destroy() {
            this.mapa?.remove();
            this.mapa = null;
        },
    });

    window.mapaSelector = (latitud, longitud) => ({
        latitud,
        longitud,
        mapa: null,
        marcador: null,

        init() {
            if (typeof L === 'undefined') {
                return;
            }

            const oscuro = document.documentElement.classList.contains('dark');
            const mapa = L.map(this.$refs.lienzo, { scrollWheelZoom: false }).setView([-33.4489, -70.6693], 11);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                className: oscuro ? 'mapa-teselas-oscuras' : '',
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(mapa);

            mapa.on('click', (evento) => this.fijar(evento.latlng.lat, evento.latlng.lng));

            this.mapa = mapa;
            this.mostrar(true);

            this.$watch('latitud', () => this.mostrar(true));
            this.$watch('longitud', () => this.mostrar(true));

            setTimeout(() => mapa.invalidateSize(), 250);
        },

        coordenadas() {
            const lat = parseFloat(this.latitud);
            const lng = parseFloat(this.longitud);

            return Number.isFinite(lat) && Number.isFinite(lng) ? [lat, lng] : null;
        },

        mostrar(centrar) {
            const punto = this.coordenadas();

            if (! punto || ! this.mapa) {
                return;
            }

            if (! this.marcador) {
                this.marcador = L.marker(punto, { draggable: true, autoPan: true }).addTo(this.mapa);
                this.marcador.on('dragend', () => {
                    const posicion = this.marcador.getLatLng();
                    this.fijar(posicion.lat, posicion.lng);
                });
            } else {
                this.marcador.setLatLng(punto);
            }

            if (centrar) {
                this.mapa.setView(punto, Math.max(this.mapa.getZoom(), 16));
            }
        },

        fijar(lat, lng) {
            this.latitud = Number(lat.toFixed(6));
            this.longitud = Number(lng.toFixed(6));
        },

        destroy() {
            this.mapa?.remove();
            this.mapa = null;
        },
    });
</script>
