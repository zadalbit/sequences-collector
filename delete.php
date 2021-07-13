<?php
@include('db.php');

if (empty($mysqli)) {
	$mysqli = new mysqli("localhost", "user", "password", "database");
	if (!$mysqli->set_charset("utf8mb4")) {
	    printf("Ошибка при загрузке набора символов utf8mb4: %s\n", $mysqli->error);
	    exit();
	}
}

function deleteSuboperations($has_relation, $mysqli) {
	$query = "SELECT * FROM subprocesses WHERE parent_process_id=".$has_relation['process_id'];
	$result = $mysqli->query($query);
	$subprocesses_rows = $result->fetch_all(MYSQLI_ASSOC);

	if (!empty($subprocesses_rows)) {
		foreach ($subprocesses_rows as $subprocess_row) {
			$query = "SELECT * FROM processes_relations WHERE subprocess_id = ".$subprocess_row['id']." and related_to_process_id=".$relation['process_id'];
			$result = $mysqli->query($query);
			$has_relation = $result->fetch_assoc();

			if (!empty($has_relation)) {
				$query = "DELETE FROM `subprocesses` WHERE `id` = '".$subprocess_row['id']."';";
				$result = $mysqli->query($query);

				$query = "DELETE FROM `processes_relations` WHERE `id` = '".$has_relation['id']."';";
				$result = $mysqli->query($query);

				deleteSuboperations($has_relation);
			}
		}
	}
}

function deleteRelation($id, $mysqli) {
	$query = "SELECT * FROM `subprocesses` WHERE `id` = ".$id;
	$result = $mysqli->query($query);
	$relation = $result->fetch_assoc();
	if (!empty($relation)) {
		$query = "SELECT * FROM subprocesses WHERE parent_process_id=".$relation['parent_process_id']." and goes_after_process_id=".$relation['process_id'];
		$result = $mysqli->query($query);
		$subprocesses_rows = $result->fetch_all(MYSQLI_ASSOC);
		
		if (!empty($subprocesses_rows)) {
			foreach ($subprocesses_rows as $subprocess_row) {
				$query = "UPDATE `subprocesses` SET `goes_after_process_id` = ".$relation['goes_after_process_id']." WHERE `id` = '".$subprocess_row['id']."';";

				$result = $mysqli->query($query);
			}
		}

		$query = "DELETE FROM `subprocesses` WHERE `id` = '".$relation['id']."';";
		$result = $mysqli->query($query);

		$query = "DELETE FROM `processes_relations` WHERE `subprocess_id` = '".$relation['id']."';";
		$result = $mysqli->query($query);

		$query = "SELECT * FROM subprocesses WHERE parent_process_id=".$relation['process_id'];
		$result = $mysqli->query($query);
		$subprocesses_rows = $result->fetch_all(MYSQLI_ASSOC);
		if (!empty($subprocesses_rows)) {
			foreach ($subprocesses_rows as $subprocess_row) {
				$query = "SELECT * FROM processes_relations WHERE subprocess_id = ".$subprocess_row['id']." and related_to_process_id=".$relation['process_id'];
				$result = $mysqli->query($query);
				$has_relation = $result->fetch_assoc();

				if (!empty($has_relation)) {
					$query = "DELETE FROM `subprocesses` WHERE `id` = '".$subprocess_row['id']."';";
					$result = $mysqli->query($query);

					$query = "DELETE FROM `processes_relations` WHERE `id` = '".$has_relation['id']."';";
					$result = $mysqli->query($query);
					deleteSuboperations($has_relation, $mysqli);
				}
			}
		}
	}
}

if (!empty($_GET['sub-id'])) {
	deleteRelation($_GET['sub-id'], $mysqli);
} else {
	if (!empty($_GET['process-id'])) {
		$query = "SELECT * FROM subprocesses WHERE process_id=".$_GET['process-id'];
		$result = $mysqli->query($query);
		$subprocesses_rows = $result->fetch_all(MYSQLI_ASSOC);
		
		foreach ($subprocesses_rows as $subprocess_row) {
			deleteRelation($subprocess_row['id'], $mysqli);
		}
	}
}
?>