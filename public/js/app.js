// CONFIGURACIÓN DE APIS
const API_USER = 'http://localhost:81';
const API_FLIGHT = 'http://localhost:8080';

// =====================================================
// INICIALIZACIÓN
// =====================================================
const token = localStorage.getItem('token');
const role = localStorage.getItem('role');

if (token) {
    mostrarContenidoPrincipal();
} else {
    document.getElementById('login-section').style.display = 'block';
}

function mostrarContenidoPrincipal() {
    document.getElementById('login-section').style.display = 'none';
    document.getElementById('main-content').style.display = 'block';
    document.getElementById('user-info').textContent = `Rol: ${role}`;
    
    if (role === 'administrador') {
        document.getElementById('admin-section').style.display = 'block';
    } else {
        document.getElementById('gestor-section').style.display = 'block';
    }
}

// =====================================================
// LOGIN / LOGOUT
// =====================================================
document.getElementById('login-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('login-error');
    
    try {
        const res = await fetch(`${API_USER}/login`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email, password})
        });
        
        const data = await res.json();
        
        if (res.ok) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('role', data.role);
            location.reload();
        } else {
            errorDiv.textContent = data.error;
        }
    } catch (error) {
        alert(`Error de conexión a ${API_USER}`);
    }
});

document.getElementById('logout-btn')?.addEventListener('click', async () => {
    const token = localStorage.getItem('token');
    try {
        await fetch(`${API_USER}/logout`, {
            method: 'POST',
            headers: {'Authorization': token || ''}
        });
    } catch (e) {
        console.warn("Error al cerrar sesión:", e);
    }
    localStorage.clear();
    location.reload();
});

// =====================================================
// ✅ 1.1 REGISTRAR NUEVO USUARIO (solo admin)
// =====================================================
document.getElementById('btn-registrar-usuario')?.addEventListener('click', () => {
    const div = document.getElementById('users-list');
    div.innerHTML = `
        <div class="form-nuevo-usuario">
            <h4>Registrar Nuevo Usuario</h4>
            <input type="text" id="new-user-name" placeholder="Nombre completo">
            <input type="email" id="new-user-email" placeholder="Email">
            <input type="password" id="new-user-password" placeholder="Contraseña">
            <select id="new-user-role">
                <option value="administrador">Administrador</option>
                <option value="gestor" selected>Gestor</option>
            </select>
            <button onclick="registrarUsuario()">Registrar Usuario</button>
        </div>
    `;
});

async function registrarUsuario() {
    const token = localStorage.getItem('token');
    const data = {
        name: document.getElementById('new-user-name').value,
        email: document.getElementById('new-user-email').value,
        password: document.getElementById('new-user-password').value,
        role: document.getElementById('new-user-role').value
    };
    
    try {
        const res = await fetch(`${API_USER}/register`, {
            method: 'POST',
            headers: {'Authorization': token, 'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await res.json();
        alert(result.message || result.error);
        document.getElementById('btn-list-users').click(); // Recargar lista
    } catch (error) {
        alert('Error de conexión');
    }
}

// =====================================================
// ✅ 1.8, 1.9, 1.10 - LISTAR, ACTUALIZAR Y CAMBIAR ROL DE USUARIOS
// =====================================================
document.getElementById('btn-list-users')?.addEventListener('click', async () => {
    const token = localStorage.getItem('token');
    const div = document.getElementById('users-list');
    
    // Botón para registrar nuevo usuario
    div.innerHTML = `<button id="btn-registrar-usuario">Registrar Nuevo Usuario</button><hr>`;
    document.getElementById('btn-registrar-usuario').addEventListener('click', () => {
        document.getElementById('btn-registrar-usuario').click();
    });
    
    // Cargar lista de usuarios
    try {
        const res = await fetch(`${API_USER}/users`, {
            headers: {'Authorization': token}
        });
        
        const data = await res.json();
        
        if (res.ok) {
            div.innerHTML += data.data.map(u => `
                <div class="item">
                    <p><strong>${u.name}</strong> (${u.email})</p>
                    <p>Rol: ${u.role}</p>
                    <input type="text" id="name-${u.id}" value="${u.name}" placeholder="Nuevo nombre">
                    <select id="role-${u.id}">
                        <option value="administrador" ${u.role==='administrador'?'selected':''}>Administrador</option>
                        <option value="gestor" ${u.role==='gestor'?'selected':''}>Gestor</option>
                    </select>
                    <button onclick="actualizarUsuario(${u.id})">Actualizar</button>
                    <button onclick="eliminarUsuario(${u.id})">Eliminar</button>
                </div>
            `).join('');
        } else {
            div.innerHTML += `<p class="error">${data.error}</p>`;
        }
    } catch (error) {
        div.innerHTML += `<p class="error">Error de conexión</p>`;
    }
});

async function actualizarUsuario(id) {
    const token = localStorage.getItem('token');
    const name = document.getElementById(`name-${id}`).value;
    const role = document.getElementById(`role-${id}`).value;
    
    try {
        const res = await fetch(`${API_USER}/users/${id}`, {
            method: 'PUT',
            headers: {'Authorization': token, 'Content-Type': 'application/json'},
            body: JSON.stringify({name, role})
        });
        
        const data = await res.json();
        alert(data.message || data.error);
        document.getElementById('btn-list-users').click(); // Recargar lista
    } catch (error) {
        alert('Error de conexión');
    }
}

async function eliminarUsuario(id) {
    if (!confirm('¿Eliminar usuario?')) return;
    
    const token = localStorage.getItem('token');
    try {
        const res = await fetch(`${API_USER}/users/${id}`, {
            method: 'DELETE',
            headers: {'Authorization': token}
        });
        
        const data = await res.json();
        alert(data.message || data.error);
        document.getElementById('btn-list-users').click(); // Recargar lista
    } catch (error) {
        alert('Error de conexión');
    }
}

// =====================================================
// ✅ 2.1, 2.2, 2.3, 2.4, 2.5 - CRUD COMPLETO DE VUELOS
// =====================================================
document.getElementById('btn-list-flights')?.addEventListener('click', async () => {
    const token = localStorage.getItem('token');
    const div = document.getElementById('flights-list');
    
    // Formulario de búsqueda
    div.innerHTML = `
        <div class="search-form">
            <h4>Buscar Vuelos</h4>
            <input type="text" id="search-origin" placeholder="Origen">
            <input type="text" id="search-destination" placeholder="Destino">
            <input type="date" id="search-date" placeholder="Fecha">
            <button onclick="buscarVuelos()">Buscar</button>
        </div>
        <div id="vuelos-resultado"></div>
        <button onclick="mostrarFormularioVuelo()">Registrar Nuevo Vuelo</button>
    `;
    
    await cargarVuelos(); // Cargar todos inicialmente
});

async function cargarVuelos(url = `${API_FLIGHT}/flights`) {
    const token = localStorage.getItem('token');
    const div = document.getElementById('vuelos-resultado');
    
    try {
        const res = await fetch(url, {
            headers: {'Authorization': token}
        });
        
        const data = await res.json();
        
        if (res.ok) {
            div.innerHTML = data.data.map(f => `
                <div class="item" id="vuelo-${f.id}">
                    <h4>${f.origin} → ${f.destination}</h4>
                    <p>Fecha: ${f.departure}</p>
                    <p>Precio: $${f.price}</p>
                    <p>Nave ID: ${f.nave_id}</p>
                    <button onclick="mostrarFormularioEditarVuelo(${f.id})">Modificar</button>
                    <button onclick="eliminarVuelo(${f.id})">Eliminar</button>
                </div>
            `).join('');
        } else {
            div.innerHTML = `<p class="error">${data.error}</p>`;
        }
    } catch (error) {
        div.innerHTML = `<p class="error">Error de conexión a API de vuelos</p>`;
    }
}

// ✅ 2.3 Buscar Vuelos
async function buscarVuelos() {
    const origin = document.getElementById('search-origin').value;
    const destination = document.getElementById('search-destination').value;
    const date = document.getElementById('search-date').value;
    
    const params = new URLSearchParams();
    if (origin) params.append('origin', origin);
    if (destination) params.append('destination', destination);
    if (date) params.append('date', date);
    
    await cargarVuelos(`${API_FLIGHT}/flights/search?${params}`);
}

// ✅ 2.1 Registrar Vuelo
function mostrarFormularioVuelo() {
    const div = document.getElementById('vuelos-resultado');
    div.innerHTML += `
        <div class="form-nuevo-vuelo">
            <h4>Registrar Nuevo Vuelo</h4>
            <input type="number" id="vuelo-nave-id" placeholder="ID de Nave">
            <input type="text" id="vuelo-origin" placeholder="Origen">
            <input type="text" id="vuelo-destination" placeholder="Destino">
            <input type="datetime-local" id="vuelo-departure" placeholder="Salida">
            <input type="datetime-local" id="vuelo-arrival" placeholder="Llegada">
            <input type="number" id="vuelo-price" placeholder="Precio">
            <button onclick="registrarVuelo()">Registrar</button>
        </div>
    `;
}

async function registrarVuelo() {
    const token = localStorage.getItem('token');
    const data = {
        nave_id: document.getElementById('vuelo-nave-id').value,
        origin: document.getElementById('vuelo-origin').value,
        destination: document.getElementById('vuelo-destination').value,
        departure: document.getElementById('vuelo-departure').value,
        arrival: document.getElementById('vuelo-arrival').value,
        price: document.getElementById('vuelo-price').value
    };
    
    try {
        const res = await fetch(`${API_FLIGHT}/flights`, {
            method: 'POST',
            headers: {'Authorization': token, 'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await res.json();
        alert(result.message || result.error);
        document.getElementById('btn-list-flights').click(); // Recargar
    } catch (error) {
        alert('Error de conexión');
    }
}

// ✅ 2.4 MODIFICAR VUELO
function mostrarFormularioEditarVuelo(id) {
    const vueloDiv = document.getElementById(`vuelo-${id}`);
    
    // Crear formulario de edición
    const editDiv = document.createElement('div');
    editDiv.className = 'form-editar-vuelo';
    editDiv.innerHTML = `
        <h5>Modificar Vuelo ID: ${id}</h5>
        <input type="number" id="edit-nave-id-${id}" placeholder="ID de Nave">
        <input type="text" id="edit-origin-${id}" placeholder="Origen">
        <input type="text" id="edit-destination-${id}" placeholder="Destino">
        <input type="datetime-local" id="edit-departure-${id}" placeholder="Salida">
        <input type="number" id="edit-price-${id}" placeholder="Precio">
        <button onclick="modificarVuelo(${id})">Guardar Cambios</button>
        <button onclick="document.getElementById('btn-list-flights').click()">Cancelar</button>
    `;
    
    vueloDiv.appendChild(editDiv);
    
    // Cargar datos actuales
    try {
        fetch(`${API_FLIGHT}/flights/${id}`, {
            headers: {'Authorization': localStorage.getItem('token')}
        })
        .then(res => res.json())
        .then(data => {
            if (data.data) {
                const f = data.data;
                document.getElementById(`edit-nave-id-${id}`).value = f.nave_id;
                document.getElementById(`edit-origin-${id}`).value = f.origin;
                document.getElementById(`edit-destination-${id}`).value = f.destination;
                document.getElementById(`edit-departure-${id}`).value = f.departure;
                document.getElementById(`edit-price-${id}`).value = f.price;
            }
        });
    } catch (e) {
        console.log("No se pudo cargar datos automáticamente");
    }
}

async function modificarVuelo(id) {
    const token = localStorage.getItem('token');
    const data = {
        nave_id: document.getElementById(`edit-nave-id-${id}`).value,
        origin: document.getElementById(`edit-origin-${id}`).value,
        destination: document.getElementById(`edit-destination-${id}`).value,
        departure: document.getElementById(`edit-departure-${id}`).value,
        price: document.getElementById(`edit-price-${id}`).value
    };
    
    try {
        const res = await fetch(`${API_FLIGHT}/flights/${id}`, {
            method: 'PUT',
            headers: {'Authorization': token, 'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await res.json();
        alert(result.message || result.error);
        document.getElementById('btn-list-flights').click(); // Recargar lista
    } catch (error) {
        alert('Error de conexión');
    }
}

// ✅ 2.5 Eliminar Vuelo
async function eliminarVuelo(id) {
    if (!confirm('¿Eliminar vuelo?')) return;
    
    const token = localStorage.getItem('token');
    try {
        const res = await fetch(`${API_FLIGHT}/flights/${id}`, {
            method: 'DELETE',
            headers: {'Authorization': token}
        });
        
        const data = await res.json();
        alert(data.message || data.error);
        document.getElementById('btn-list-flights').click(); // Recargar
    } catch (error) {
        alert('Error de conexión');
    }
}

// =====================================================
// ✅ GESTOR - RESERVAS (4.1, 4.2, 4.3, 4.4)
// =====================================================
document.getElementById('btn-ver-reservas')?.addEventListener('click', async () => {
    const token = localStorage.getItem('token');
    const div = document.getElementById('reservas-list');
    
    div.innerHTML = `
        <div class="nueva-reserva">
            <h4>Nueva Reserva</h4>
            <input type="number" id="reserva-flight-id" placeholder="ID del Vuelo">
            <button onclick="crearReserva()">Crear Reserva</button>
        </div>
        <div id="mis-reservas"></div>
    `;
    
    await cargarMisReservas();
});

async function cargarMisReservas() {
    const token = localStorage.getItem('token');
    const div = document.getElementById('mis-reservas');
    
    try {
        const res = await fetch(`${API_FLIGHT}/reservations`, {
            headers: {'Authorization': token}
        });
        
        const data = await res.json();
        
        if (res.ok) {
            div.innerHTML = data.data.map(r => `
                <div class="item">
                    <p>Vuelo ID: ${r.flight_id} - Estado: ${r.status}</p>
                    <button onclick="cancelarReserva(${r.id})">Cancelar</button>
                </div>
            `).join('');
        } else {
            div.innerHTML = `<p class="error">${data.error}</p>`;
        }
    } catch (error) {
        div.innerHTML = `<p class="error">Error de conexión</p>`;
    }
}

// ✅ 4.1 Crear Reserva
async function crearReserva() {
    const token = localStorage.getItem('token');
    const flightId = document.getElementById('reserva-flight-id').value;
    
    try {
        const res = await fetch(`${API_FLIGHT}/reservations`, {
            method: 'POST',
            headers: {'Authorization': token, 'Content-Type': 'application/json'},
            body: JSON.stringify({flight_id: flightId})
        });
        
        const data = await res.json();
        alert(data.message || data.error);
        document.getElementById('btn-ver-reservas').click(); // Recargar
    } catch (error) {
        alert('Error de conexión');
    }
}

// ✅ 4.4 Cancelar Reserva
async function cancelarReserva(id) {
    if (!confirm('¿Cancelar reserva?')) return;
    
    const token = localStorage.getItem('token');
    try {
        const res = await fetch(`${API_FLIGHT}/reservations/${id}/cancel`, {
            method: 'PUT',
            headers: {'Authorization': token}
        });
        
        const data = await res.json();
        alert(data.message || data.error);
        document.getElementById('btn-ver-reservas').click(); // Recargar
    } catch (error) {
        alert('Error de conexión');
    }
}