<?php

@include('db.php');

if (empty($mysqli)) {
	$mysqli = new mysqli("localhost", "user", "password", "database");
	if (!$mysqli->set_charset("utf8mb4")) {
	    printf("Ошибка при загрузке набора символов utf8mb4: %s\n", $mysqli->error);
	    exit();
	}
}

function getSubprocessForLiArray(&$li_array, &$li_space_array, $show_as_related_to_id, $lvl, $processes_row, $mysqli) {
	$query = "SELECT * FROM subprocesses WHERE goes_after_process_id = 0 and parent_process_id = ".$processes_row['id'];
	$result = $mysqli->query($query);
	$relations_rows = $result->fetch_all(MYSQLI_ASSOC);

	$f_i = true;

	$array = [
		'li_array' => [],
		'li_space_array' => []
	];
	if (!empty($relations_rows)) {
		foreach ($relations_rows as $relation_row) {
			$query = "SELECT * FROM processes_relations WHERE related_to_process_id = ".$show_as_related_to_id." and subprocess_id = ".$relation_row['id'];
			$result = $mysqli->query($query);
			$related = $result->fetch_assoc();

			if (!empty($related)) {
				$li_html = '';
				$li_space_html = '';

				if ($f_i) {
					$class = 'border-top';
					$f_i = false;
				} else {
					$class = '';
				}

				$query = "SELECT * FROM processes WHERE id = ".$relation_row['process_id'];
				$result = $mysqli->query($query);
				$subprocess =  $result->fetch_assoc();

				$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

				$li_html .= '<div class="li-cell border-top">';

				$li_html .= '<div class="li-connect-cell border-left">';

				$li_html .= '<div class="main-div">
									<center><pre>id: '.$subprocess['id'].'</pre></center><br>
									'.implode(' ', $full_sequence_array).'
								</div>
								<div class="actions-div">
									<span class="action-div add-action">
										Додати в план
									</span>
									<span class="action-div delete-action">
										Видалити
									</span>
									<span class="action-div">
										Замінити на альтернативу
									</span>
									<label class="action-div hide-other-'.$subprocess['id'].'" for="main-hide-other-'.$subprocess['id'].'">
										<input type="checkbox" class="hide-other-'.$subprocess['id'].'"  name="" id="main-hide-other-'.$subprocess['id'].'"> Приховати всі інакші в рядку
									</label>
									<label class="action-div" for="add-next">
										<input type="radio"> Додати наступну конкретизацію (вправо)
									</label>
									<label class="action-div" for="main-show-child">
										<input type="radio"> Додати конкретизацію (вниз)
									</label>
									<label class="action-div show-action-'.$subprocess['id'].'" for="main-show-child-for-'.$subprocess['id'].'">
										<input class="show-action-'.$subprocess['id'].'" type="radio" name="child_for_'.$processes_row['id'].'" id="main-show-child-for-'.$subprocess['id'].'"> Показати конкретизації
									</label>
								</div>
							</div>
						</div>';

				$li_array[$lvl][$processes_row['id']][$subprocess['id']]['html'] = $li_html;
				$li_array[$lvl][$processes_row['id']][$subprocess['id']]['id'] = $subprocess['id'];
				
				$li_space_html .= '<div class="li-connect-cell"></div>';

				$li_space_array[$lvl][$processes_row['id']][$subprocess['id']]['html'] = $li_space_html;
				$li_space_array[$lvl][$processes_row['id']][$subprocess['id']]['id'] = $subprocess['id'];

				$array = getNextSubprocessForLiArray($show_as_related_to_id, $lvl, $subprocess, $processes_row, $mysqli);

				$li_array[$lvl][$processes_row['id']] = array_merge($li_array[$lvl][$processes_row['id']], $array['li_array']);
				
				$li_space_array[$lvl][$processes_row['id']] = array_merge($li_space_array[$lvl][$processes_row['id']], $array['li_space_array']);
				
				getSubprocessForLiArray($li_array, $li_space_array, $show_as_related_to_id, $lvl + 1, $subprocess, $mysqli);	
			}
		}
	}
}

function getNextSubprocessForLiArray($show_as_related_to_id, $lvl, $subprocess_before, $processes_row, $mysqli) {
	$query = "SELECT * FROM subprocesses WHERE goes_after_process_id = ".$subprocess_before['id']." and parent_process_id = ".$processes_row['id'];
	$result = $mysqli->query($query);
	$next_subrelations_rows = $result->fetch_all(MYSQLI_ASSOC);

	$array = [
		'li_array' => [],
		'li_space_array' => []
	];

	$li_array[$lvl] = [
		$processes_row['id'] => []
	];
	$li_space_array[$lvl] = [
		$processes_row['id'] => []
	];

	if (!empty($next_subrelations_rows)) {
		foreach ($next_subrelations_rows as $next_subrelations_row) {
			$li_html = '';
			$li_space_html = '';
			$class = '';

			$query = "SELECT * FROM processes WHERE id = ".$next_subrelations_row['process_id'];
			$result = $mysqli->query($query);
			$subprocess =  $result->fetch_assoc();

			$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);
			
			$text = empty($class) ? "" : $class." border-left";
			$li_html .= '<div class="li-cell '.$text.'">';

			$text = empty($class) ? "border-left" : "";
			$li_html .= '<div class="li-connect-cell '.$text.'">';

			$li_html .= '<div class="main-div">
								<center><pre>id: '.$subprocess['id'].' (next after '.$subprocess_before['id'].')</pre></center><br>
								'.implode(' ', $full_sequence_array).'
							</div>
							<div class="actions-div">
								<span class="action-div add-action">
									Додати в план
								</span>
								<span class="action-div delete-action">
									Видалити
								</span>
								<span class="action-div">
									Замінити на альтернативу
								</span>
								<label class="action-div hide-other-'.$subprocess['id'].'" for="main-hide-other-'.$subprocess['id'].'">
									<input type="checkbox" class="hide-other-'.$subprocess['id'].'" name="" id="main-hide-other-'.$subprocess['id'].'"> Приховати всі інакші в рядку
								</label>
								<label class="action-div" for="add-next">
									<input type="radio"> Додати наступну конкретизацію (вправо)
								</label>
								<label class="action-div" for="main-show-child">
									<input type="radio"> Додати конкретизацію (вниз)
								</label>
								<label class="action-div show-action-'.$subprocess['id'].'" for="main-show-child-for-'.$subprocess['id'].'">
									<input class="show-action-'.$subprocess['id'].'" type="radio" name="child_for_'.$processes_row['id'].'" id="main-show-child-for-'.$subprocess['id'].'"> Показати конкретизації
								</label>
							</div>
						</div>
					</div>';

			$li_array[$lvl][$processes_row['id']][$subprocess['id']]['html'] = $li_html;
			$li_array[$lvl][$processes_row['id']][$subprocess['id']]['id'] = $subprocess['id'];
			
			$li_space_html .= '<div class="li-connect-cell"></div>';

			$li_space_array[$lvl][$processes_row['id']][$subprocess['id']]['html'] = $li_space_html;
			$li_space_array[$lvl][$processes_row['id']][$subprocess['id']]['id'] = $subprocess['id'];

			$array = getNextSubprocessForLiArray($show_as_related_to_id, $lvl, $subprocess, $processes_row, $mysqli);

			$li_array[$lvl][$processes_row['id']] = array_merge($li_array[$lvl][$processes_row['id']], $array['li_array']);
			
			$li_space_array[$lvl][$processes_row['id']] = array_merge($li_space_array[$lvl][$processes_row['id']], $array['li_space_array']);

			getSubprocessForLiArray($li_array, $li_space_array, $show_as_related_to_id, $lvl + 1, $subprocess, $mysqli);
		}

		$array['li_array'] = $li_array[$lvl][$processes_row['id']];
		$array['li_space_array'] = $li_space_array[$lvl][$processes_row['id']];
	}

	return $array;
}

function getSubprocessesForLayer(&$row_html, $parent_process_id, $i, $subprocess_before, $processes_row, $mysqli) {
	$query = "SELECT * FROM subprocesses WHERE goes_after_process_id = ".$subprocess_before['id']." and parent_process_id = ".$processes_row['id'];
	$result = $mysqli->query($query);
	$next_subrelations_rows = $result->fetch_all(MYSQLI_ASSOC);
	$spaces = '';
	
	for ($k=0; $k < $i; $k++) { 
		$spaces = $spaces . '        ';
	}

	$query = "SELECT * FROM subprocesses WHERE goes_after_process_id = 0 and parent_process_id = ".$subprocess_before['id'];
	$result = $mysqli->query($query);
	$subrelations_rows = $result->fetch_all(MYSQLI_ASSOC);

	if (!empty($subrelations_rows)) {
		$f_i = true;
		
		foreach ($subrelations_rows as $subrelations_row) {
			$query = "SELECT * FROM processes_relations WHERE related_to_process_id = ".$parent_process_id." and subprocess_id = ".$subrelations_row['id'];
			$result = $mysqli->query($query);
			$related = $result->fetch_assoc();

			if (!empty($related)) {
				if ($f_i) {
					$row_html = $row_html . $spaces . '            \'Перелік визначень підпроцесів\': [<br>';
					$f_i = false;
				}
				$query = "SELECT * FROM processes WHERE id = ".$subrelations_row['process_id'];
				$result = $mysqli->query($query);
				$subprocess =  $result->fetch_assoc();

				$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

				$row_html = $row_html . $spaces . '                {<br>';
				$row_html = $row_html . $spaces . '                    \'id\': \'</pre>'.$subprocess['id'].'<pre>\',<br>';
				$row_html = $row_html . $spaces . '                    \'Визначення\': \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';
				$row_html = $row_html . $spaces . '                    \'Доступні дії\': {<br>';
				$row_html = $row_html . $spaces . '                        \'Переглянути\': \'<a  href="/subprocess.php?show-process='.$subprocess['id'].'&show-as-related-to-id='.$parent_process_id.'" target="content">Переглянути в нижній частині екрану</a>\'<br>';
				$row_html = $row_html . $spaces . '                    }<br>';

				getSubprocessesForLayer($row_html, $parent_process_id, $i + 1, $subprocess, $subprocess_before, $mysqli);

				$row_html = $row_html . $spaces . '                }<br>';
			}
		}

		if ($f_i) {
			$row_html = $row_html . $spaces . '            \'Перелік визначень підпроцесів\': []<br>';
		} else {
			$row_html = $row_html . $spaces . '            ]<br>';
		}
	} else {
		$row_html = $row_html . $spaces . '            \'Перелік визначень підпроцесів\': []<br>';
	}

	if (!empty($next_subrelations_rows)) {
		$f_i = true;
		foreach ($next_subrelations_rows as $next_subrelation_row) {
			$query = "SELECT * FROM processes_relations WHERE related_to_process_id = ".$parent_process_id." and subprocess_id = ".$next_subrelation_row['id'];
			$result = $mysqli->query($query);
			$related = $result->fetch_assoc();

			if (!empty($related)) {
				if ($f_i) {
					$row_html = $row_html . $spaces . '                \'Після чого слідує\': [<br>';
					$f_i = false;
				}
				$query = "SELECT * FROM processes WHERE id = ".$next_subrelation_row['process_id'];
				$result = $mysqli->query($query);
				$subprocess =  $result->fetch_assoc();

				$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

				$row_html = $row_html . $spaces . '                {<br>';
				$row_html = $row_html . $spaces . '                    \'id\': \'</pre>'.$subprocess['id'].'<pre>\',<br>';
				$row_html = $row_html . $spaces . '                    \'Визначення\': \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';
				$row_html = $row_html . $spaces . '                    \'Доступні дії\': {<br>';
				$row_html = $row_html . $spaces . '                        \'Переглянути\': \'<a  href="/subprocess.php?show-process='.$subprocess['id'].'&show-as-related-to-id='.$parent_process_id.'" target="content">Переглянути в нижній частині екрану</a>\'<br>';
				$row_html = $row_html . $spaces . '                    }<br>';


				getSubprocessesForLayer($row_html, $parent_process_id, $i + 1, $subprocess, $processes_row, $mysqli);

				$row_html = $row_html . $spaces . '                }<br>';
			}
		}
		if ($f_i) {
			$row_html = $row_html . $spaces . '            \'Після чого слідує\': []<br>';
		} else {
			$row_html = $row_html . $spaces . '            ]<br>';
		}
	} else {
		$row_html = $row_html . $spaces . '            \'Після чого слідує\': []<br>';
	}
}

function getSubprocesses(&$row_html, $parent_process_id, $i, $subprocess_before, $processes_row, $mysqli) {
	$query = "SELECT * FROM subprocesses WHERE goes_after_process_id = ".$subprocess_before['id']." and parent_process_id = ".$processes_row['id'];
	$result = $mysqli->query($query);
	$next_subrelations_rows = $result->fetch_all(MYSQLI_ASSOC);
	$spaces = '';
	
	for ($k=0; $k < $i; $k++) { 
		$spaces = $spaces . '        ';
	}

	$query = "SELECT * FROM subprocesses WHERE goes_after_process_id = 0 and parent_process_id = ".$subprocess_before['id'];
	$result = $mysqli->query($query);
	$subrelations_rows = $result->fetch_all(MYSQLI_ASSOC);

	if (!empty($subrelations_rows)) {
		$f_i = true;
		
		foreach ($subrelations_rows as $subrelations_row) {
			$query = "SELECT * FROM processes_relations WHERE related_to_process_id = ".$parent_process_id." and subprocess_id = ".$subrelations_row['id'];
			$result = $mysqli->query($query);
			$related = $result->fetch_assoc();

			if (!empty($related)) {
				if ($f_i) {
					$row_html = $row_html . $spaces . '            \'Перелік визначень підпроцесів\': [<br>';
					$f_i = false;
				}
				$query = "SELECT * FROM processes WHERE id = ".$subrelations_row['process_id'];
				$result = $mysqli->query($query);
				$subprocess =  $result->fetch_assoc();

				$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

				$row_html = $row_html . $spaces . '                {<br>';
				$row_html = $row_html . $spaces . '                    \'id\': \'</pre>'.$subprocess['id'].'<pre>\',<br>';
				$row_html = $row_html . $spaces . '                    \'Визначення\': \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';

				getSubprocesses($row_html, $parent_process_id, $i + 1, $subprocess, $subprocess_before, $mysqli);

				$row_html = $row_html . $spaces . '                }<br>';
			}
		}

		if ($f_i) {
			$row_html = $row_html . $spaces . '            \'Перелік визначень підпроцесів\': []<br>';
		} else {
			$row_html = $row_html . $spaces . '            ]<br>';
		}
	} else {
		$row_html = $row_html . $spaces . '            \'Перелік визначень підпроцесів\': []<br>';
	}

	if (!empty($next_subrelations_rows)) {
		$f_i = true;
		foreach ($next_subrelations_rows as $next_subrelation_row) {
			$query = "SELECT * FROM processes_relations WHERE related_to_process_id = ".$parent_process_id." and subprocess_id = ".$next_subrelation_row['id'];
			$result = $mysqli->query($query);
			$related = $result->fetch_assoc();

			if (!empty($related)) {
				if ($f_i) {
					$row_html = $row_html . $spaces . '                \'Після чого слідує\': [<br>';
					$f_i = false;
				}
				$query = "SELECT * FROM processes WHERE id = ".$next_subrelation_row['process_id'];
				$result = $mysqli->query($query);
				$subprocess =  $result->fetch_assoc();

				$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

				$row_html = $row_html . $spaces . '                {<br>';
				$row_html = $row_html . $spaces . '                    \'id\': \'</pre>'.$subprocess['id'].'<pre>\',<br>';
				$row_html = $row_html . $spaces . '                    \'Визначення\': \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';

				getSubprocesses($row_html, $parent_process_id, $i + 1, $subprocess, $processes_row, $mysqli);

				$row_html = $row_html . $spaces . '                }<br>';
			}
		}
		if ($f_i) {
			$row_html = $row_html . $spaces . '            \'Після чого слідує\': []<br>';
		} else {
			$row_html = $row_html . $spaces . '            ]<br>';
		}
	} else {
		$row_html = $row_html . $spaces . '            \'Після чого слідує\': []<br>';
	}
}

function getFullSequenceArray($sequence_id, $mysqli) {
	$query = "SELECT * FROM sequences WHERE id = ".$sequence_id;
	$before_sequence_result = $mysqli->query($query);
	$before_sequence = $before_sequence_result->fetch_assoc();
	$reversed_full_alternative = [];
	$alternative_piece = [];
	if ($before_sequence['phrase_id'] != 0) {
		$query = "SELECT * FROM phrases WHERE id = ".$before_sequence['phrase_id'];
		$phrase_result = $mysqli->query($query);
		$phrase_row = $phrase_result->fetch_assoc();
		$alternative_piece[] = $phrase_row['phrase'];

		$has_continue = true;
		$sub_first_id = $sequence_id;

		while($has_continue) {
			$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$sub_first_id;
			$next_continue_sequence_result = $mysqli->query($query);
			$next_continue_sequence = $next_continue_sequence_result->fetch_assoc();
			if (!empty($next_continue_sequence)) {
				$sub_first_id = $next_continue_sequence['id'];
				$query = "SELECT * FROM phrases WHERE id = ".$next_continue_sequence['phrase_id'];
				$phrase_result = $mysqli->query($query);
				$phrase_row = $phrase_result->fetch_assoc();

				$alternative_piece[] = $phrase_row['phrase'];
			} else {
				$has_continue = false;
			}
		}

		$reversed_full_alternative[] = $alternative_piece;

	} else {
		$has_continue = true;
		$sub_first_id = $before_sequence['id'];

		while($has_continue) {
			$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$sub_first_id;
			$next_continue_sequence_result = $mysqli->query($query);
			$next_continue_sequence = $next_continue_sequence_result->fetch_assoc();
			if (!empty($next_continue_sequence)) {
				$sub_first_id = $next_continue_sequence['id'];
				$query = "SELECT * FROM phrases WHERE id = ".$next_continue_sequence['phrase_id'];
				$phrase_result = $mysqli->query($query);
				$phrase_row = $phrase_result->fetch_assoc();

				$alternative_piece[] = $phrase_row['phrase'];
			} else {
				$has_continue = false;
			}
		}

		$reversed_full_alternative[] = $alternative_piece;

		$equate_to_record_id = $sequence_id;
		$has_begining = true;
		while ($has_begining) {
			$query = "SELECT * FROM sequences_equations WHERE equate_to_record_id = ".$equate_to_record_id;
			$before_sequence_equation_result = $mysqli->query($query);
			$before_sequence_equation = $before_sequence_equation_result->fetch_assoc();
			if (!empty($before_sequence_equation)) {
				$query = "SELECT * FROM sequences WHERE id = ".$before_sequence_equation['sequence_all_data_from_id'];
				$before_sequence_result = $mysqli->query($query);
				$before_sequence = $before_sequence_result->fetch_assoc();
				$alternative_piece = [];
				if ($before_sequence['phrase_id'] != 0) {
					$has_begining = false;
					$query = "SELECT * FROM phrases WHERE id = ".$before_sequence['phrase_id'];
					$phrase_result = $mysqli->query($query);
					$phrase_row = $phrase_result->fetch_assoc();
					$alternative_piece[] = $phrase_row['phrase'];

					$has_continue = true;
					$sub_first_id = $before_sequence['id'];

					while($has_continue) {
						$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$sub_first_id;
						$next_continue_sequence_result = $mysqli->query($query);
						$next_continue_sequence = $next_continue_sequence_result->fetch_assoc();
						if (!empty($next_continue_sequence)) {
							$sub_first_id = $next_continue_sequence['id'];
							$query = "SELECT * FROM phrases WHERE id = ".$next_continue_sequence['phrase_id'];
							$phrase_result = $mysqli->query($query);
							$phrase_row = $phrase_result->fetch_assoc();

							$alternative_piece[] = $phrase_row['phrase'];
						} else {
							$has_continue = false;
						}
					}

					$reversed_full_alternative[] = $alternative_piece;
				} else {
					$equate_to_record_id = $before_sequence_equation['sequence_all_data_from_id'];

					$has_continue = true;
					$sub_first_id = $equate_to_record_id;

					while($has_continue) {
						$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$sub_first_id;
						$next_continue_sequence_result = $mysqli->query($query);
						$next_continue_sequence = $next_continue_sequence_result->fetch_assoc();
						if (!empty($next_continue_sequence)) {
							$sub_first_id = $next_continue_sequence['id'];
							$query = "SELECT * FROM phrases WHERE id = ".$next_continue_sequence['phrase_id'];
							$phrase_result = $mysqli->query($query);
							$phrase_row = $phrase_result->fetch_assoc();

							$alternative_piece[] = $phrase_row['phrase'];
						} else {
							$has_continue = false;
						}
					}

					$reversed_full_alternative[] = $alternative_piece;
				}
			} else {
				$has_begining = false;
			}
		}
	}

	$full_sequence_array = [];
	$i = 0;
	foreach ($reversed_full_alternative as $alternative_piece) {
		$i = $i + 1;
		$array = $reversed_full_alternative[count($reversed_full_alternative) - $i];
		foreach ($array as $item) {
			$full_sequence_array[] = $item;
		}
	}

	return $full_sequence_array;
}

function getAllEquationContinue(&$row_html, $sequences_row_id, $continue_phrase_text, $mysqli) {
	$query = "SELECT * FROM sequences_equations WHERE sequence_all_data_from_id = ".$sequences_row_id;
	$continue_sequences_equations_result = $mysqli->query($query);
	$continue_sequences_equations = $continue_sequences_equations_result->fetch_all(MYSQLI_ASSOC);

	$next_continue_phrase_text = null;

	if (!empty($continue_sequences_equations)) {
		foreach ($continue_sequences_equations as $continue_sequences_equation) {
			$query = "SELECT * FROM alternatives WHERE sequence_id = ".$continue_sequences_equation['equate_to_record_id'];
			$result = $mysqli->query($query);
			$alternatives_rows = $result->fetch_all(MYSQLI_ASSOC);
			$alternatives = [];
			foreach ($alternatives_rows as $alternatives_row) {
				$full_sequence_array = getFullSequenceArray($alternatives_row['alternative_sequence_id'], $mysqli);
				$alternatives[] = '                    \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';
			}

			$query = "SELECT * FROM tags WHERE sequence_id = ".$continue_sequences_equation['equate_to_record_id'];
			$result = $mysqli->query($query);
			$tags_rows = $result->fetch_all(MYSQLI_ASSOC);
			$tags = [];
			foreach ($tags_rows as $tag_rows) {
				$full_sequence_array = getFullSequenceArray($tag_rows['tag_id'], $mysqli);
				$tags[] = '                    \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';
			}

			$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$continue_sequences_equation['equate_to_record_id'];
			$continue_sequences_result = $mysqli->query($query);
			$continue_sequences = $continue_sequences_result->fetch_assoc();
			if (!empty($continue_sequences)) {
				$query = "SELECT * FROM phrases WHERE id = ".$continue_sequences['phrase_id'];
				$continue_phrase_result = $mysqli->query($query);
				$continue_phrase = $continue_phrase_result->fetch_assoc();
				$next_continue_phrase_text = $continue_phrase_text . $continue_phrase['phrase'].' ';

				$has_continue = true;
				while ($has_continue) {
					$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$continue_sequences['id'];
					$continue_sequences_result = $mysqli->query($query);
					$continue_sequences = $continue_sequences_result->fetch_assoc();
					if (!empty($continue_sequences)) {
						$query = "SELECT * FROM phrases WHERE id = ".$continue_sequences['phrase_id'];
						$continue_phrase_result = $mysqli->query($query);
						$continue_phrase = $continue_phrase_result->fetch_assoc();
						
						$next_continue_phrase_text = $next_continue_phrase_text . $continue_phrase['phrase'].' ';
					} else {
						$row_html = $row_html . '            {<br>';
						$row_html = $row_html . '                \'id\': \''.$continue_sequences_equation['equate_to_record_id'].'\',<br>';
						$row_html = $row_html . '                \'Висловлювання яке визначає процес\': \'</pre>'.$next_continue_phrase_text.'<pre>\'<br>';
						if (count($alternatives) > 0) {
							$row_html = $row_html . '                \'Перелік варіантів альтернативних висловлювань\': [<br>';
							foreach ($alternatives as $alternative) {
								$row_html = $row_html . $alternative;
							}
							$row_html = $row_html . '                ]<br>';
						} else {
							$row_html = $row_html . '                \'Перелік варіантів альтернативних висловлювань\': []<br>';
						}

						if (count($tags) > 0) {
							$row_html = $row_html . '                \'Перелік висловлювань з групи до якої належить дане висловлювання\': [<br>';
							foreach ($tags as $tag) {
								$row_html = $row_html . $tag;
							}
							$row_html = $row_html . '                ],<br>';
						} else {
							$row_html = $row_html . '                \'Перелік висловлювань з групи до якої належить дане висловлювання\': []<br>';
						}

						$row_html = $row_html . '            }<br>';
						$has_continue = false;
					}
				}
			}

			$next_continue_phrase_text = !empty($next_continue_phrase_text) ? $next_continue_phrase_text : $continue_phrase_text;

			getAllEquationContinue($row_html, $continue_sequences_equation['equate_to_record_id'], $next_continue_phrase_text, $mysqli);
		}
	}
}

function insertNewSequencesFromFirst(&$saved_sequence_id, $first_sequence_id, $phrases, $mysqli) {
	$query = "INSERT INTO sequences (id, before_current_ending_id, phrase_id) VALUES (NULL, 0, 0)";

	if ($mysqli->query($query) === TRUE) {
		$query = "SELECT * FROM sequences WHERE id = '".$mysqli->insert_id."'";

		$result = $mysqli->query($query);
		$sequence_row = $result->fetch_assoc();

		$query = "INSERT INTO sequences_equations (id, sequence_all_data_from_id, equate_to_record_id) VALUES (NULL, ".$first_sequence_id.", ".$sequence_row['id'].")";

		if ($mysqli->query($query) === TRUE) {
			$saved_sequence_id = $sequence_row['id'];

			$query = "SELECT * FROM sequences_equations WHERE id = '".$mysqli->insert_id."'";

			$result = $mysqli->query($query);
			$sequences_equation_row = $result->fetch_assoc();

			foreach ($phrases as $index => $phrase) {
				if ($index > 0) {
					$query = "INSERT INTO sequences (id, before_current_ending_id, phrase_id) VALUES (NULL, ".$sequence_row['id'].", ".$phrases[$index]['id'].")";
					if ($mysqli->query($query) === TRUE) {
						$query = "SELECT * FROM sequences WHERE id = '".$mysqli->insert_id."'";

						$result = $mysqli->query($query);
						$sequence_row = $result->fetch_assoc();
					}
				}
			}
		}
	}
}

function getSequencesCoversCount($phrases, $skip_phrases, $before_current_ending_id, $mysqli) {
	$covers_count = 0;
	$return = [
		'count_sequence' => 0,
		'count_covers' => 0,
		'covers_till_id' => $before_current_ending_id
	];

	$has_continue = true;
	$i = $skip_phrases;
	while ($has_continue) {
		$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$before_current_ending_id;
		$continue_sequences_result = $mysqli->query($query);
		$continue_sequences = $continue_sequences_result->fetch_assoc();

		if (!empty($continue_sequences)) {
			if (!empty($phrases[$i])) {
				if ($continue_sequences['phrase_id'] == $phrases[$i]['id']) {
					$covers_count = $covers_count + 1;
					$before_current_ending_id = $continue_sequences['id'];
				} else {
					$has_continue = false;
					return $return;
				}
			} else {
				$has_continue = false;
				$covers_count = $covers_count + $skip_phrases;
				$return['count_sequence'] =$covers_count + 1;
				$return['count_covers'] = $covers_count;
				$return['covers_till_id'] = $before_current_ending_id;

				return $return;
			}
			
		} else {
			if($i > $skip_phrases) {
				$has_continue = false;
				$covers_count = $covers_count + $skip_phrases;
				$return['count_sequence'] =$covers_count;
				$return['count_covers'] = $covers_count;

				return $return;
			} else {
				$return['count_sequence'] = $skip_phrases;
				$return['count_covers'] = $skip_phrases;

				$has_continue = false;
				//$covers_count = $skip_phrases;
				return $return;
			}
		}

		$i = $i + 1;
	}

	return $return;
}

function getCountedArray(&$counted, &$for_rearchivations, &$saved_sequence_id, $next_sequences_equations_row, $phrases, $another_count, $mysqli) {
	$next_another_count = getSequencesCoversCount($phrases, $another_count, $next_sequences_equations_row['equate_to_record_id'], $mysqli);
	$found_exect = false;

	if ($next_another_count['count_covers'] == $next_another_count['count_sequence'] && $next_another_count['count_sequence'] == count($phrases)) {
		$found_exect = true;
		$saved_sequence_id = $next_sequences_equations_row['equate_to_record_id'];

		if ($next_sequences_equations_row['hidden'] == 1) {
			$query = "UPDATE `sequences_equations` SET hidden` = CONV('0', 2, 10) + 0 WHERE `id` = '".$next_sequences_equations_row['id']."';";
			if ($mysqli->query($query) === TRUE) {
				$next_sequences_equations_row['hidden'] = 0;
			}
		}
		return $found_exect;
	} else {
		if ($next_another_count['count_covers'] > 0) {
			if (count($phrases) > $next_another_count['count_sequence'] && $next_another_count['count_covers'] == $next_another_count['count_sequence']) {
				$counted[$next_another_count['count_covers']] = $next_sequences_equations_row;

				$query = "SELECT * FROM sequences_equations WHERE sequence_all_data_from_id = ".$next_sequences_equations_row['equate_to_record_id'];
				$result = $mysqli->query($query);
				$next_sequences_equations_rows = $result->fetch_all(MYSQLI_ASSOC);
				if (!empty($next_sequences_equations_rows)) {
					foreach($next_sequences_equations_rows as $next_sequences_equations_row) {
						$found_exect = getCountedArray($counted, $for_rearchivations, $saved_sequence_id, $next_sequences_equations_row, $phrases, $next_another_count['count_covers'], $mysqli);
						if ($found_exect) {
							return $found_exect;
						}
					}
				}
			} else {
				$for_rearchivations[] = [
					'entity' => $next_sequences_equations_row,
					'covers_till_id' => $next_another_count['covers_till_id']
				];
			}
		}
	}
}

if(!empty($_POST['data'])) {
	$pieces = explode(' ', $_POST['data']);
	$continuing = false;
	$has_starter = false;
	$starter_last_index = 0;
	$starter_id = 0;

	$phrases = [];

	if (count($pieces) > 0) {
		foreach($pieces as $index => $piece) {
			$query = "SELECT * FROM phrases WHERE phrase = '".$piece."'";

			$result = $mysqli->query($query);
			$phrases[$index] = $result->fetch_assoc();

			if (empty($phrases[$index])) {
				$sql = "INSERT INTO phrases (id, phrase) VALUES (NULL, '".$piece."')";

				if ($mysqli->query($sql) === TRUE) {
					$query = "SELECT * FROM phrases WHERE phrase = '".$piece."'";

					$result = $mysqli->query($query);
					$phrases[$index] = $result->fetch_assoc();
				} else {
				  echo "Error: " . $sql . "<br>" . $mysqli->error;
				}
			}
		}

		if(isset($_POST['is_sequence'])) {
			$saved_sequence_id = 0;
			$query = "SELECT * FROM sequences WHERE before_current_ending_id = 0 and phrase_id = ".$phrases[0]['id'];
			$result = $mysqli->query($query);
			$sequence_row = $result->fetch_assoc();
			if (!empty($sequence_row)) {
				if(count($phrases) > 1) {
					$first_sequence_id = $sequence_row['id'];
					$query = "SELECT * FROM sequences_equations WHERE sequence_all_data_from_id = ".$sequence_row['id'];
					$result = $mysqli->query($query);
					$sequences_equations_rows = $result->fetch_all(MYSQLI_ASSOC);
					if (!empty($sequences_equations_rows)) {
						$counted = [];
						$for_rearchivations = [];
						$already_processed = false;
						$found_exect = false;
						foreach ($sequences_equations_rows as $sequences_equations_row) {
							$count = getSequencesCoversCount($phrases, 1, $sequences_equations_row['equate_to_record_id'], $mysqli);

							if ($count['count_covers'] == $count['count_sequence'] && $count['count_sequence'] == count($phrases)) {
								$already_processed = true;

								$saved_sequence_id = $sequences_equations_row['equate_to_record_id'];

								if ($sequences_equations_row['hidden'] == 1) {
									$query = "UPDATE `sequences_equations` SET `hidden` = CONV('0', 2, 10) + 0 WHERE `id` = '".$sequences_equations_row['id']."';";
									if ($mysqli->query($query) === TRUE) {
										$sequences_equations_row['hidden'] = 0;
									}
								}
								break;
							} else {
								if ($count['count_covers'] > 0) {
									if (count($phrases) > $count['count_sequence'] && $count['count_covers'] == $count['count_sequence']) {
										$counted[$count['count_covers']] = $sequences_equations_row;

										$query = "SELECT * FROM sequences_equations WHERE sequence_all_data_from_id = ".$sequences_equations_row['equate_to_record_id'];
										$result = $mysqli->query($query);
										$next_sequences_equations_rows = $result->fetch_all(MYSQLI_ASSOC);
										if (!empty($next_sequences_equations_rows)) {
											foreach($next_sequences_equations_rows as $next_sequences_equations_row) {
												$found_exect = getCountedArray($counted, $for_rearchivations, $saved_sequence_id, $next_sequences_equations_row, $phrases, $count['count_covers'], $mysqli);
												if ($found_exect) {
													break;
												}
											}
										}
									} else {
										$for_rearchivations[] = [
											'entity' => $sequences_equations_row,
											'covers_till_id' => $count['covers_till_id']
										];
									}
								}
							}
						}

						if (!$found_exect && !$already_processed) {
							if (!empty($counted)) {
								$key = array_key_last($counted);
								$query = "INSERT INTO sequences (id, before_current_ending_id, phrase_id) VALUES (NULL, 0, 0)";

								if ($mysqli->query($query) === TRUE) {
									$equate_to_record_id = $mysqli->insert_id;

									$query = "INSERT INTO sequences_equations (id, sequence_all_data_from_id, equate_to_record_id) VALUES (NULL, ".$counted[$key]['equate_to_record_id'].", ".$equate_to_record_id.")";
									$sequence_id = $equate_to_record_id;
									if ($mysqli->query($query) === TRUE) {
										foreach ($phrases as $i => $phrase) {
											if ($i >= $key) {
												$query = "INSERT INTO sequences (id, before_current_ending_id, phrase_id) VALUES (NULL, ".$sequence_id.", ".$phrase['id'].")";
												if ($mysqli->query($query) === TRUE) {
													$sequence_id = $mysqli->insert_id;
												}
											}
										}
									}
								}

								$saved_sequence_id = $equate_to_record_id;

								if (!empty($for_rearchivations)) {
									foreach($for_rearchivations as $for_rearchivation)
									{
										$query = "UPDATE `sequences_equations` SET `sequence_all_data_from_id` = ".$equate_to_record_id." WHERE `id` = '".$for_rearchivation['entity']['id']."';";

										if ($mysqli->query($query) === TRUE) {
											$has_continue = true;
											$continue_sequences = [
												'id' => $for_rearchivation['entity']['equate_to_record_id']
											];

											while ($has_continue) {
												$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$continue_sequences['id'];
												$continue_sequences_result = $mysqli->query($query);
												$continue_sequences = $continue_sequences_result->fetch_assoc();
												if (!empty($continue_sequences)) {
													if ( $for_rearchivation['covers_till_id'] == $continue_sequences['id']) {
														$has_continue = false;

														$query = "DELETE FROM `sequences` WHERE `id` = '".$continue_sequences['id']."'";
														$result = $mysqli->query($query);

														$query = "UPDATE `sequences` SET `before_current_ending_id` = ".$for_rearchivation['entity']['equate_to_record_id']." WHERE `before_current_ending_id` = '".$continue_sequences['id']."'";
														$result = $mysqli->query($query);
													} else {
														$query = "DELETE FROM `sequences` WHERE `id` = '".$continue_sequences['id']."'";
														$result = $mysqli->query($query);
													}
												} else {
													$has_continue = false;
												}
											}
										}
									}
								}
							} else {
								$query = "INSERT INTO sequences (id, before_current_ending_id, phrase_id) VALUES (NULL, 0, 0)";

								if ($mysqli->query($query) === TRUE) {
									$equate_to_record_id = $mysqli->insert_id;

									$query = "INSERT INTO sequences_equations (id, sequence_all_data_from_id, equate_to_record_id) VALUES (NULL, ".$first_sequence_id.", ".$equate_to_record_id.")";
									$sequence_id = $equate_to_record_id;
									$key = 1;
									if ($mysqli->query($query) === TRUE) {
										foreach ($phrases as $i => $phrase) {
											if ($i >= $key) {
												$query = "INSERT INTO sequences (id, before_current_ending_id, phrase_id) VALUES (NULL, ".$sequence_id.", ".$phrase['id'].")";
												if ($mysqli->query($query) === TRUE) {
													$sequence_id = $mysqli->insert_id;
												}
											}
										}
									}
								}

								$saved_sequence_id = $equate_to_record_id;

								if (!empty($for_rearchivations)) {
									foreach($for_rearchivations as $for_rearchivation)
									{
										$query = "UPDATE `sequences_equations` SET `sequence_all_data_from_id` = ".$equate_to_record_id." WHERE `id` = '".$for_rearchivation['entity']['id']."';";

										if ($mysqli->query($query) === TRUE) {
											$has_continue = true;
											$continue_sequences = [
												'id' => $for_rearchivation['entity']['equate_to_record_id']
											];

											while ($has_continue) {
												$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$continue_sequences['id'];
												$continue_sequences_result = $mysqli->query($query);
												$continue_sequences = $continue_sequences_result->fetch_assoc();
												if (!empty($continue_sequences)) {
													if ( $for_rearchivation['covers_till_id'] == $continue_sequences['id']) {
														$has_continue = false;

														$query = "DELETE FROM `sequences` WHERE `id` = '".$continue_sequences['id']."'";
														$result = $mysqli->query($query);

														$query = "UPDATE `sequences` SET `before_current_ending_id` = ".$for_rearchivation['entity']['equate_to_record_id']." WHERE `before_current_ending_id` = '".$continue_sequences['id']."'";
														$result = $mysqli->query($query);
													} else {
														$query = "DELETE FROM `sequences` WHERE `id` = '".$continue_sequences['id']."'";
														$result = $mysqli->query($query);
													}
												} else {
													$has_continue = false;
												}
											}
										}
									}
								}
							}
						}
					} else {
						insertNewSequencesFromFirst($saved_sequence_id, $first_sequence_id, $phrases, $mysqli);
					}
				} else {
					$saved_sequence_id = $sequence_row['id'];
				} 
			} else {
				$query = "INSERT INTO sequences (id, before_current_ending_id, phrase_id) VALUES (NULL, 0, '".$phrases[0]['id']."')";

				if ($mysqli->query($query) === TRUE) {
					$first_sequence_id = $mysqli->insert_id;

					if(count($phrases) > 1) {
						insertNewSequencesFromFirst($saved_sequence_id, $first_sequence_id, $phrases, $mysqli);
					} else {
						$saved_sequence_id = $first_sequence_id;
					}
				}
			}


			$query = "SELECT * FROM processes WHERE sequence_id = ".$saved_sequence_id;
			$result = $mysqli->query($query);
			$process = $result->fetch_assoc();

			if (isset($_POST['process_id']) && $_POST['process_id'] != 0) {
				$query = "SELECT * FROM processes WHERE id = ".$_POST['process_id'];
				$result = $mysqli->query($query);
				$passed_process = $result->fetch_assoc();
				if (!empty($passed_process) && empty($process)) {
					$query = "UPDATE `processes` SET `sequence_id` = ".$saved_sequence_id." WHERE `id` = ".$_POST['process_id'];
					$result = $mysqli->query($query); 
				}
			} else {
				if (empty($process)) {
					$query = "INSERT INTO processes (id, sequence_id) VALUES (NULL, ".$saved_sequence_id.")";

					if ($mysqli->query($query) === TRUE) {
						$process_id = $mysqli->insert_id;

						$query = "SELECT * FROM processes WHERE id = ".$process_id;
						$result = $mysqli->query($query);
						$process = $result->fetch_assoc();
					}
				}
			}
/*
			$query = "SELECT * FROM subprocesses WHERE parent_process_id = 0 and goes_after_process_id = 0 and process_id = ".$process['id'];
			$result = $mysqli->query($query);
			$subprocess = $result->fetch_assoc();
*/

			if (isset($_POST['parent_process_id']) && $_POST['parent_process_id'] != 0) {
				$query = "SELECT * FROM subprocesses WHERE process_id = ".$process['id']." and parent_process_id = ".$_POST['parent_process_id'];
				$result = $mysqli->query($query);
				$subprocess = $result->fetch_assoc();

				if (empty($subprocess)) {
					$query = "INSERT INTO subprocesses (id, parent_process_id, goes_after_process_id, process_id) VALUES (NULL, ".$_POST['parent_process_id'].", ".$_POST['goes_after_process_id'].", ".$process['id'].")";

					if ($mysqli->query($query) === TRUE) {
						$subprocess_relation_id = $mysqli->insert_id;
					}
				} else {
					$query = "UPDATE `subprocesses` SET `goes_after_process_id` = ".$_POST['goes_after_process_id']." WHERE `id` = ".$subprocess['id'];
					$result = $mysqli->query($query); 
					$subprocess_relation_id = $subprocess['id'];
				}

				if ($_POST['related_to_process_id'] != 0) {
					$query = "SELECT * FROM processes_relations WHERE subprocess_id = ".$subprocess_relation_id." and related_to_process_id = ".$_POST['related_to_process_id'];
					$result = $mysqli->query($query);
					$relation = $result->fetch_assoc();

					if (empty($relation)) {
						$query = "INSERT INTO processes_relations (id, related_to_process_id, subprocess_id) VALUES (NULL,".$_POST['related_to_process_id'].", ".$subprocess_relation_id.")";

						if ($mysqli->query($query) === TRUE) {
							$process_relation_id = $mysqli->insert_id;
						}
					}
				}
			}
			
		}		
	}
}

if (!empty($_GET['show-process'])) {
	$query = "SELECT * FROM processes WHERE id = ".$_GET['show-process'];
	$result = $mysqli->query($query);
	$processes_row = $result->fetch_assoc();

	if (!empty($processes_row)) {
?>
		<!DOCTYPE html>
		<html>
		<head>
			<script src="https://code.jquery.com/jquery-3.5.0.js"></script>

			<meta charset="utf-8">
			<title>Процеси</title>
			<style type="text/css">
				pre {
					display: inline;
				}

				.text-right {
					text-align: right;
					padding: 10px;
				}

				h3 {
					height: 30px;
					display: inline-block;
					padding: 0px;
					margin: 0px;
					line-height: 30px;
					padding-left: 10px;
				}

				img {
					height: 30px;
					display: inline-block;
					padding: 0px;
					margin: 0px;
					float: left;
				}

				.process {
					text-align: center;
				}

				.process ul {
					margin: 0px;
					padding: 0px;
					display: flex;
        			justify-content: center;
        			margin: 0 auto;
				}

				.process ul li {
					margin: 0px;
					text-align: left;
					padding-left: 30px;
					width: 300px;
					text-decoration: none;
					align-self: stretch;
					display: block;
					box-sizing: border-box;
				}

				.li-connect-cell {
					padding-top: 30px;
				}

				.add-action {
					background: #c8ffc8;
				}

				.li-cell {
					display: block;
					height: 100%;
					box-sizing: border-box;
				}

				.border-left {
					border-left: 4px solid #a0a0a0;
					border-right: 4px solid #a0a0a0;
				}

				.main-div {
					border: 3px solid #a0a0a0;
					border-bottom: 0px;
					padding: 20px;
					white-space: normal;
					border-left: 2px solid #a0a0a0;
					border-right: 2px solid #a0a0a0;
				}

				.actions-div {
					border: 3px solid #a0a0a0;
					border-top: 0px;
					border-left: 2px solid #a0a0a0;
					border-right: 2px solid #a0a0a0;
					background: #c0c0c0;
				}

				.action-div {
					border-top: 2px solid #a0a0a0;
					padding: 10px;
					display: block;
					cursor: pointer;
				}

				.border-top {
					border-top: 6px solid #a0a0a0;
				}

				.delete-action {
					background: #ffd5d5;
				}

				.overflow-y {
					overflow-y: scroll;
					margin: 0 auto;
					max-width: 100%;
				}

				.selected {
					position: relative;
					z-index: 9998; 
					height: 50px; 
					width: 294px; 
					border-left: 4px solid #a0a0a0; 
					border-right: 4px solid #a0a0a0;
				} 

				.hidden {
					display: none !important;
				}

				/*.padding-0 {
					padding: 0px !important;
					width: 270px !important;
				}*/

				.connection {
					border-left: 4px solid #a0a0a0; 
					border-right: 4px solid #a0a0a0;
				}

				.way-to-center {
					border-bottom: 6px solid #a0a0a0;
				}

				.height-100 {
					height: 100%;
					box-sizing: border-box;
				}
			</style>
			<script>
				$(document).ready(function(){
					$("#main-show-child").change(function() {
						console.log(this.checked);
					    if(this.checked) {
					        $(".main-show-child").show();
					    } else {
					    	$(".main-show-child").hide();
					    }
					});
				});
			</script>
		</head>
		<body>
			<?php 
				
				$full_sequence_array = getFullSequenceArray($processes_row['sequence_id'], $mysqli);

			?>
			<h2>Поступовий розвиток конкретизацій</h2>
			<div class="process">
				<div class="overflow-y">
					<ul>
						<li class="padding-0" style="position: relative;z-index: 9999;">

							<div class="li-cell border-left">
								<div>
									<div class="main-div">
										<center><pre>id: <?php echo $processes_row['id']; ?></pre></center><br>
										<?php echo implode(' ', $full_sequence_array); ?>
									</div>
									<div class="actions-div">
										<label class="action-div" for="main-show-child">
											<input type="checkbox" name="main-show-child" id="main-show-child" checked> Показати конкретизації
										</label>
										<label class="action-div" for="main-show-child">
											<input type="radio" checked> Додати конкретизацію
										</label>
									</div>
								</div>
							</div>
						</li>
					</ul>
					<ul>
						<li class="li-spaces" style="position: relative;z-index: 9999;">
							<div class="li-cell border-left">
								<div class="li-connect-cell">
										
								</div>
							</div>
						</li>
					</ul>
				</div>
				<?php
					$show_as_related_to_id = $processes_row['id'];
					
					$query = "SELECT * FROM subprocesses WHERE goes_after_process_id = 0 and parent_process_id = ".$processes_row['id'];
					$result = $mysqli->query($query);
					$relations_rows = $result->fetch_all(MYSQLI_ASSOC);

					$f_i = true;

					$li_array = [];
					$li_space_array = [];

					$lvl = 1;
					$li_array[$lvl] = [
						$processes_row['id'] => []
					];
					$li_space_array[$lvl] = [
						$processes_row['id'] => []
					];

					foreach ($relations_rows as $relation_row) 
					{
						$li_html = '';
						$li_space_html = '';

						if ($f_i) {
							$class = 'border-top';
							$f_i = false;
						} else {
							$class = '';
						}

						$query = "SELECT * FROM processes WHERE id = ".$relation_row['process_id'];
						$result = $mysqli->query($query);
						$subprocess =  $result->fetch_assoc();

						$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

						$li_html .= '<div class="li-cell '.$class.'">';

						$li_html .= '<div class="li-connect-cell border-left">';

						$li_html .= '<div class="main-div">
											<center><pre>id: '.$subprocess['id'].'</pre></center><br>
											'.implode(' ', $full_sequence_array).'
										</div>
										<div class="actions-div">
											<span class="action-div add-action">
												Додати в план
											</span>
											<span class="action-div delete-action">
												Видалити
											</span>
											<span class="action-div">
												Замінити на альтернативу
											</span>
											<label class="action-div hide-other-'.$subprocess['id'].'" for="main-hide-other-'.$subprocess['id'].'">
												<input type="checkbox" class="hide-other-'.$subprocess['id'].'"  name="" id="main-hide-other-'.$subprocess['id'].'"> Приховати всі інакші в рядку
											</label>
											<label class="action-div" for="add-next">
												<input type="radio"> Додати наступну конкретизацію (вправо)
											</label>
											<label class="action-div" for="main-show-child">
												<input type="radio"> Додати конкретизацію (вниз)
											</label>
											<label class="action-div show-action-'.$subprocess['id'].'" for="main-show-child-for-'.$subprocess['id'].'">
												<input class="show-action-'.$subprocess['id'].'" type="radio" name="child_for_'.$processes_row['id'].'" id="main-show-child-for-'.$subprocess['id'].'"> Показати конкретизації
											</label>
										</div>
									</div>
								</div>';

						$li_array[$lvl][$processes_row['id']][$subprocess['id']]['html'] = $li_html;
						$li_array[$lvl][$processes_row['id']][$subprocess['id']]['id'] = $subprocess['id'];
						
						$li_space_html .= '<div class="li-connect-cell"></div>';

						$li_space_array[$lvl][$processes_row['id']][$subprocess['id']]['html'] = $li_space_html;
						$li_space_array[$lvl][$processes_row['id']][$subprocess['id']]['id'] = $subprocess['id'];

						getSubprocessForLiArray($li_array, $li_space_array, $show_as_related_to_id, $lvl + 1, $subprocess, $mysqli);

						$query = "SELECT * FROM subprocesses WHERE goes_after_process_id = ".$subprocess['id']." and parent_process_id = ".$processes_row['id'];
						$result = $mysqli->query($query);
						$next_relations_rows = $result->fetch_all(MYSQLI_ASSOC);
						if (!empty($next_relations_rows)) {
							foreach ($next_relations_rows as $next_relations_row) {
								$li_html = '';
								$li_space_html = '';
								$class = '';

								$query = "SELECT * FROM processes WHERE id = ".$next_relations_row['process_id'];
								$result = $mysqli->query($query);
								$subprocess =  $result->fetch_assoc();

								$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

								$text = empty($class) ? "" : $class." border-left";
								$li_html .= '<div class="li-cell '.$text.'">';
								
								$text = empty($class) ? "border-left" : "";
								$li_html .= '<div class="li-connect-cell '.$text.'">';

								$li_html .= '<div class="main-div">
													<center><pre>id: '.$subprocess['id'].' (next after '.$next_relations_row['goes_after_process_id'].')</pre></center><br>
													'.implode(' ', $full_sequence_array).'
												</div>
												<div class="actions-div">
													<span class="action-div add-action">
														Додати в план
													</span>
													<span class="action-div delete-action">
														Видалити
													</span>
													<span class="action-div">
														Замінити на альтернативу
													</span>
													<label class="action-div hide-other-'.$subprocess['id'].'" for="main-hide-other-'.$subprocess['id'].'">
														<input type="checkbox" class="hide-other-'.$subprocess['id'].'" name="" id="main-hide-other-'.$subprocess['id'].'"> Приховати всі інакші в рядку
													</label>
													<label class="action-div" for="add-next">
														<input type="radio"> Додати наступну конкретизацію (вправо)
													</label>
													<label class="action-div" for="main-show-child">
														<input type="radio"> Додати конкретизацію (вниз)
													</label>
													<label class="action-div show-action-'.$subprocess['id'].'" for="main-show-child-for-'.$subprocess['id'].'">
														<input class="show-action-'.$subprocess['id'].'" type="radio" name="child_for_'.$processes_row['id'].'" id="main-show-child-for-'.$subprocess['id'].'"> Показати конкретизації
													</label>
												</div>
											</div>
										</div>
									';

								$li_array[$lvl][$processes_row['id']][$subprocess['id']]['html'] = $li_html;
								$li_array[$lvl][$processes_row['id']][$subprocess['id']]['id'] = $subprocess['id'];
								
								$li_space_html .= '<div class="li-connect-cell"></div>';

								$li_space_array[$lvl][$processes_row['id']][$subprocess['id']]['html'] = $li_space_html;
								$li_space_array[$lvl][$processes_row['id']][$subprocess['id']]['id'] = $subprocess['id'];

								$array = getNextSubprocessForLiArray($show_as_related_to_id, $lvl, $subprocess, $processes_row, $mysqli);

								$li_array[$lvl][$processes_row['id']] = array_merge($array['li_array'], $li_array[$lvl][$processes_row['id']]);
								
								$li_space_array[$lvl][$processes_row['id']] = array_merge($array['li_space_array'], $li_space_array[$lvl][$processes_row['id']]);	

								getSubprocessForLiArray($li_array, $li_space_array, $show_as_related_to_id, $lvl + 1, $subprocess, $mysqli);
							}
						}
					}

					$li_text = '';
					$li_space_text = '';
					$ul_html = '';
					foreach ($li_array as $level => $lis) {
						$ul_html .= '<div class="main-show-child overflow-y" style="margin-top:-6px;">';
						foreach ($lis as $parent_id => $children) {
							$ul_html .= '<ul style="width:'.(count($children)*300).'px;">';
							$f_i = true;
							$half = ceil(count($children) / 2);
							
							foreach ($children as $id => $child) {
								if ($f_i) {
									$class = 'border-top';
									$f_i = false;
								} else {
									$class = '';
								}
								
								$hidden = '';

								if ($level != 1) {
									$hidden = 'hidden';
								}

								$text = empty($class) ? "border-top" : "";
								$ul_html .= '<li class="item-lvl item-lvl-'.$level.' '.$hidden.' item-parent-id-'.$parent_id.' '.$text.'" data-parent-id="'.$parent_id.'" data-lvl="'.$level.'" style="position: relative;z-index: 9998;" data-id="'.$child['id'].'">';
								$ul_html .= $child['html'];
								$ul_html .= '</li>
								<script type="text/javascript">
										$(document).ready(function(){
											
											$(".hide-other-'.$child['id'].'").click(function() {
												if(document.getElementById("main-hide-other-'.$child['id'].'").checked) {
													
												} else {

												}
											});
											$(".show-action-'.$child['id'].'").click(function() {
												if(document.getElementById("main-show-child-for-'.$child['id'].'").checked) {
													$( ".item-lvl-'.$level.'" ).each(function( index ) {
											    	  $( this ).find( ".li-connect-cell" ).removeClass("height-100");
													  if ($( this ).data(\'id\') == \''.$child['id'].'\') {
													  	$( this ).find( ".li-connect-cell" ).addClass("height-100");
													  }
													});
													$( ".space-item-lvl-'.$level.' .li-cell" ).each(function( index ) {
											    		$( this ).removeClass("connection");
											    		$( this ).removeClass("way-to-center");
											    		if ($( this ).data(\'id\') == \''.$child['id'].'\') {
														  	$( this ).addClass("connection");
														}
											    	});
											    	var numb = 0
											    	$( ".space-item-lvl-'.$level.'" ).each(function( index ) {
											    		$( this ).removeClass("way-to-center");
											    		
											    		if($(this).hasClass("connection-for-'.$child['id'].'")) {
											    			numb = numb + 1;
											    		}

											    		if(numb != 1) {
											    			if ($(this).hasClass("connection-for-'.$child['id'].'")) {
																$( this ).addClass("way-to-center");
															}
										    			} else {
										    				if ($(this).hasClass("connection-for-'.$child['id'].'")) {
										    					$( this ).find( ".li-cell" ).addClass("way-to-center");
															}
										    			}
														
											    	});
											    	$( ".item-lvl" ).each(function( index ) {
											    		if (parseInt($( this ).data(\'lvl\')) > '.($level + 1).') {
											    			$( this ).removeClass("hidden");
											    			$( this ).addClass("hidden");
											    		}
											    	});
											    	$( ".item-lvl-'.($level + 1).'" ).each(function( index ) {
											    	  $( this ).removeClass("hidden");
													  if ($( this ).data(\'parent-id\') != \''.$child['id'].'\') {
													  	$( this ).addClass("hidden");
													  }
													});
											    }
											});
											$("#main-show-child-for-'.$child['id'].'").change(function() {
											    if(this.checked) {
											    	$( ".item-lvl-'.$level.'" ).each(function( index ) {
											    	  $( this ).find( ".li-connect-cell" ).removeClass("height-100");
													  if ($( this ).data(\'id\') == \''.$child['id'].'\') {
													  	$( this ).find( ".li-connect-cell" ).addClass("height-100");
													  }
													});
													$( ".space-item-lvl-'.$level.' .li-cell" ).each(function( index ) {
											    		$( this ).removeClass("connection");
											    		$( this ).removeClass("way-to-center");
											    		if ($( this ).data(\'id\') == \''.$child['id'].'\') {
														  	$( this ).addClass("connection");
														}
											    	});
											    	var numb = 0
											    	$( ".space-item-lvl-'.$level.'" ).each(function( index ) {
											    		$( this ).removeClass("way-to-center");

											    		if($(this).hasClass("connection-for-'.$child['id'].'")) {
											    			numb = numb + 1;
											    		}

											    		if(numb != 1) {
											    			if ($(this).hasClass("connection-for-'.$child['id'].'")) {
																$( this ).addClass("way-to-center");
															}
										    			} else {
										    				if ($(this).hasClass("connection-for-'.$child['id'].'")) {
										    					$( this ).find( ".li-cell" ).addClass("way-to-center");
															}
										    			}
														
											    	});
											    	$( ".item-lvl" ).each(function( index ) {
											    		if (parseInt($( this ).data(\'lvl\')) > '.($level + 1).') {
											    			$( this ).removeClass("hidden");
											    			$( this ).addClass("hidden");
											    		}
											    	});
											    	$( ".item-lvl-'.($level + 1).'" ).each(function( index ) {
											    	  $( this ).removeClass("hidden");
											    	  console.log($( this ).data(\'parent-id\'));
													  if ($( this ).data(\'parent-id\') != \''.$child['id'].'\') {
													  	$( this ).addClass("hidden");
													  }
													});
											    }
											});
										});
									</script>';
							}
							$ul_html .= '</ul>';
							$ul_html .= '<ul style="width:'.(count($children)*300).'px;">';

							$i = 0;
							$half = ceil(count($children) / 2);
							$exect_half = count($children) % 2 == 0 ? true : false;
							$connection_till_center_for = '';

							foreach ($li_space_array[$level][$parent_id] as $index => $li_space) {
								$i = $i + 1;

								if ($i == 1) {
									$connection_till_center_for .= 'connection-for-'.$li_space["id"];
								} else {
									if ($exect_half) {
										if ($i > 1 && $i < $half) {
											$connection_till_center_for .= ' connection-for-'.$li_space["id"];
										} else {
											if ($i > 1 && $i == $half) {
												$connection_till_center_for .= ' connection-for-'.$li_space["id"];
											} else {
												if ($i > 1 && $i > $half) {
													$connection_till_center_for = 'connection-for-'.$li_space["id"];
													$array = $li_space_array[$level][$parent_id];
													$reversed_array = array_reverse($array);
													$k = 0;
													foreach ($reversed_array as $from_last_value) {
														$k = $k + 1;
														if ($k <= (count($children) - $i)) {
															$connection_till_center_for .= ' connection-for-'.$from_last_value['id'];
														}
													}
												}
											}
										}
									} else {
										if ($i > 1 && $i < $half) {
											$connection_till_center_for .= ' connection-for-'.$li_space["id"];
										} else {
											if ($i > 1 && $i == $half) {
												$connection_till_center_for .= ' connection-for-'.$li_space["id"];
												$k = 0;
												foreach ($li_space_array[$level][$parent_id] as $check_id => $value) {
													$k = $k + 1;
													if ($k > $i) {
														$connection_till_center_for .= ' connection-for-'.$value['id'];
													}
												}
											} else {
												if ($i > 1 && $i > $half) {
													$connection_till_center_for = 'connection-for-'.$li_space['id'];
													$array = $li_space_array[$level][$parent_id];
													$reversed_array = array_reverse($array);
													$k = 0;
													foreach ($reversed_array as $from_last_value) {
														$k = $k + 1;
														if ($k <= (count($children) - $i)) {
															$connection_till_center_for .= ' connection-for-'.$from_last_value['id'];
														}
													}
												}
											}
										}
									}
								}

								$ul_html .= '<li class="item-lvl space-item-lvl-'.$level.' '.$connection_till_center_for.' item-lvl-'.$level.' item-parent-id-'.$parent_id.' li-spaces" style="position: relative;z-index: 9998;" data-parent-id="'.$parent_id.'" data-lvl="'.$level.'" data-numb="'.$i.'" data-id="'.$li_space["id"].'">
									<div class="li-cell" data-numb="'.$i.'" data-id="'.$li_space["id"].'">';
								$ul_html .= $li_space['html'];
								$ul_html .= '</div></li>';
							}

							$ul_html .= '</ul>';
						}
						$ul_html .= '</div>';
					}

					echo $ul_html;
				?>
			</div>


			<!-- <form action="/" method="GET">
				Знайти найбільш відповідну послідовність
				<input type="text" name="finder">
				<input type="submit" name="submit">
			</form> -->
			<table class="table">

					<!-- <td>Альтернативні вирази</td> -->
			</table>
		</body>
		</html>

		<?php
	}
}
?>
