<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_POST && isset($_POST['eliminar_proyecto']) && verifyCSRFToken($_POST['csrf_token'])) {
    $projectId = (int) $_POST['eliminar_proyecto'];

    try {
        $db = getDB();
        $db->beginTransaction();
        $db->delete("DELETE FROM MEDIOS WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
        $db->delete("DELETE FROM COMENTARIOS WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
        $db->delete("DELETE FROM FAVORITOS WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
        $result = $db->delete("DELETE FROM PROYECTOS WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
        $db->commit();
        if ($result) {
            setFlashMessage('success', 'Proyecto eliminado correctamente');
        } else {
            setFlashMessage('error', 'El proyecto no existe o ya fue eliminado');
        }
    } catch (Exception $e) {
        $db->rollback();
        setFlashMessage('error', 'Error al eliminar el proyecto');
        error_log("Error eliminando proyecto: " . $e->getMessage());
    }
}
header('Location: proyectos.php');
exit;
?>