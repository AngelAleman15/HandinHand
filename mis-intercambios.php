<?php
/**
 * Página: Mis intercambios activos
 * Muestra todos los intercambios en curso con panel de seguimiento
 */

$page_title = 'Mis Intercambios | HandinHand';
$body_class = 'body-intercambios';

require_once 'includes/header.php';

if (!isLoggedIn()) {
    header('Location: iniciarsesion.php');
    exit;
}

$user_id = $_SESSION['user_id'];
?>

<link rel="stylesheet" href="css/intercambios-activos.css?v=<?php echo time(); ?>">

<div class="container-intercambios">
    <div class="page-header">
        <h1><i class="fas fa-handshake"></i> Mis Intercambios</h1>
        <p class="subtitle">Gestiona y da seguimiento a tus intercambios</p>
    </div>
    
    <!-- Tabs de navegación -->
    <div class="tabs-intercambios">
        <button class="tab-btn active" data-tab="activos">
            <i class="fas fa-sync-alt"></i> Activos
        </button>
        <button class="tab-btn" data-tab="completados">
            <i class="fas fa-check-circle"></i> Completados
        </button>
    </div>
    
    <!-- Contenido de tabs -->
    <div id="tab-activos" class="tab-content" style="display: block;">
        <div id="intercambios-activos-container" class="intercambios-grid">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Cargando intercambios activos...</p>
            </div>
        </div>
        
        <div id="empty-state-activos" class="empty-state" style="display: none;">
            <i class="fas fa-box-open"></i>
            <h3>No tienes intercambios activos</h3>
            <p>Cuando aceptes una propuesta de intercambio, aparecerá aquí para que puedas dar seguimiento.</p>
        </div>
    </div>
    
    <div id="tab-completados" class="tab-content" style="display: none;">
        <div id="intercambios-completados-container" class="intercambios-grid">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Cargando intercambios completados...</p>
            </div>
        </div>
        
        <div id="empty-state-completados" class="empty-state" style="display: none;">
            <i class="fas fa-trophy"></i>
            <h3>No tienes intercambios completados</h3>
            <p>Tus intercambios completados aparecerán aquí.</p>
        </div>
    </div>
</div>

<!-- Modal de acciones de seguimiento -->
<div id="modal-acciones-seguimiento" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-tasks"></i> Acciones de Seguimiento</h3>
            <button class="btn-close" onclick="cerrarModalAcciones()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="acciones-body">
            <!-- Se llenará dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal de denuncia -->
<div id="modal-denuncia" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Reportar Problema</h3>
            <button class="btn-close" onclick="cerrarModalDenuncia()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="form-denuncia" onsubmit="return enviarDenuncia(event)">
                <input type="hidden" id="denuncia-seguimiento-id">
                
                <div class="form-group">
                    <label>Motivo de la denuncia</label>
                    <select id="denuncia-motivo" class="form-control" required>
                        <option value="">Selecciona un motivo</option>
                        <option value="no_aparecio">No apareció al encuentro</option>
                        <option value="producto_distinto">Producto distinto al acordado</option>
                        <option value="producto_danado">Producto dañado o defectuoso</option>
                        <option value="actitud_inapropiada">Actitud inapropiada</option>
                        <option value="estafa">Intento de estafa</option>
                        <option value="otro">Otro motivo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Descripción detallada</label>
                    <textarea id="denuncia-descripcion" class="form-control" rows="5" 
                              placeholder="Describe lo sucedido con el mayor detalle posible..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="cerrarModalDenuncia()">Cancelar</button>
                    <button type="submit" class="btn-danger">
                        <i class="fas fa-flag"></i> Enviar Denuncia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/intercambios-activos.js?v=<?php echo time(); ?>"></script>

<?php require_once 'includes/footer.php'; ?>
