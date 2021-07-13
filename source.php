<?php
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

function getSequenceId($sequence) {
	$pieces = explode(' ', $sequence);
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

		return $saved_sequence_id;
	} else {
		return 0;
	}
}
?>