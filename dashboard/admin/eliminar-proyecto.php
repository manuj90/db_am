<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

if ($_POST && isset($_POST['eliminar_proyecto']) && verifyCSRFToken($_POST['csrf_token'])) {
    $projectId = (int) $_POST['eliminar_proyecto'];

    try {
        $db = getDB();
        $proyecto = $db->selectOne("SELECT id_proyecto FROM PROYECTOS WHERE id_proyecto = :id", ['id' => $projectId]);

        if (!$proyecto) {
            setFlashMessage('error', 'El proyecto no existe');
            header('Location: proyectos.php');
            exit;
        }

        $db->transaction(function ($db) use ($projectId) {
            $db->delete("DELETE FROM MEDIOS WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
            $db->delete("DELETE FROM COMENTARIOS WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
            $db->delete("DELETE FROM FAVORITOS WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
            $db->delete("DELETE FROM CALIFICACIONES WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
            $rowsAffected = $db->delete("DELETE FROM PROYECTOS WHERE id_proyecto = :project_id", ['project_id' => $projectId]);
            if ($rowsAffected === 0) {
                throw new Exception('No se pudo eliminar el proyecto');
            }

            return $rowsAffected;
        });
        setFlashMessage('success', 'Proyecto eliminado correctamente junto con todos sus datos relacionados');

    } catch (Exception $e) {
        setFlashMessage('error', 'Error al eliminar el proyecto: ' . $e->getMessage());
    }

} else {
    setFlashMessage('error', 'Solicitud inválida para eliminar proyecto');
}

header('Location: proyectos.php');
exit;
?>