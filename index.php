<?php

@include('db.php');

if (empty($mysqli)) {
	$mysqli = new mysqli("localhost", "user", "password", "database");
	if (!$mysqli->set_charset("utf8mb4")) {
	    printf("Ошибка при загрузке набора символов utf8mb4: %s\n", $mysqli->error);
	    exit();
	}
}

function getSubprocesses(&$row_html, $i, $subprocess, $processes_row, $mysqli) {
	$query = "SELECT * FROM processes_relations WHERE goes_after_process_id = ".$subprocess['id']." and parent_process_id = ".$processes_row['id'];
	$result = $mysqli->query($query);
	$subrelations_rows = $result->fetch_all(MYSQLI_ASSOC);
	$spaces = '';

	for ($k=0; $k < $i; $k++) { 
		$spaces = $spaces . '    ';
	}

	if (!empty($subrelations_rows)) {
		$row_html = $row_html . $spaces . '                \'Наступним виконується\': [<br>';
		foreach ($subrelations_rows as $subrelation_row) {
			$query = "SELECT * FROM processes WHERE id = ".$subrelation_row['process_id'];
			$result = $mysqli->query($query);
			$subprocess =  $result->fetch_assoc();

			$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

			$row_html = $row_html . $spaces . '                    {<br>';
			$row_html = $row_html . $spaces . '                        \'id\': \'</pre>'.$subprocess['id'].'<pre>\',<br>';
			$row_html = $row_html . $spaces . '                        \'Визначення\': \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';

			getSubprocesses($row_html, $i + 1, $subprocess, $processes_row, $mysqli);

			$row_html = $row_html . $spaces . '                    }<br>';
		}
		$row_html = $row_html . $spaces . '                ]<br>';
	} else {
		$row_html = $row_html . $spaces . '                \'Наступним виконується\': []<br>';
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

			if (!empty($_GET['show-sequences'])) {
				if (!empty($_POST['alternative_id']) && $_POST['alternative_id'] != 0 && $saved_sequence_id != 0) {
					$query = "SELECT * FROM alternatives WHERE sequence_id = ".$_POST['alternative_id']." and alternative_sequence_id = ".$saved_sequence_id;
					$result = $mysqli->query($query);
					$alternative = $result->fetch_assoc();
					if (empty($alternative)) {
						$query = "INSERT INTO alternatives (id, sequence_id, alternative_sequence_id) VALUES (NULL, ".$_POST['alternative_id'].", ".$saved_sequence_id.")";
						$result = $mysqli->query($query);
					}
					$query = "SELECT * FROM alternatives WHERE sequence_id = ".$saved_sequence_id." and alternative_sequence_id = ".$_POST['alternative_id'];
					$result = $mysqli->query($query);
					$alternative = $result->fetch_assoc();
					if (empty($alternative)) {
						$query = "INSERT INTO alternatives (id, sequence_id, alternative_sequence_id) VALUES (NULL, ".$saved_sequence_id.", ".$_POST['alternative_id'].")";
						$result = $mysqli->query($query);
					}
				}

				if (!empty($_POST['tag_id']) && $_POST['tag_id'] != 0 && $saved_sequence_id != 0) {
					$query = "SELECT * FROM tags WHERE sequence_id = ".$_POST['tag_id']." and tag_id = ".$saved_sequence_id;
					$result = $mysqli->query($query);
					$tag = $result->fetch_assoc();
					if (empty($tag)) {
						$query = "INSERT INTO tags (id, sequence_id, tag_id) VALUES (NULL, ".$_POST['tag_id'].", ".$saved_sequence_id.")";
						$result = $mysqli->query($query);
					}
				}
			} else {
				$query = "SELECT * FROM processes WHERE sequence_id = ".$saved_sequence_id;
				$result = $mysqli->query($query);
				$process = $result->fetch_assoc();

				if (empty($process)) {
					$query = "INSERT INTO processes (id, sequence_id) VALUES (NULL, ".$saved_sequence_id.")";

					if ($mysqli->query($query) === TRUE) {
						$process_id = $mysqli->insert_id;

						$query = "SELECT * FROM processes WHERE id = ".$process_id;
						$result = $mysqli->query($query);
						$process = $result->fetch_assoc();
					}
				}

				if ($_POST['parent_process_id'] != 0) {
					$query = "INSERT INTO processes_relations (id, parent_process_id, goes_after_process_id, process_id) VALUES (NULL, ".$_POST['parent_process_id'].", ".$_POST['goes_after_process_id'].", ".$process['id'].")";

					if ($mysqli->query($query) === TRUE) {
						$process_relation_id = $mysqli->insert_id;
					}
				}
			}
		}		
	}
}

if (!empty($_GET['show-sequences'])) {

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>
	<style type="text/css">
		pre {
			display: inline;
		}

		.table {
			border: 1px solid #d0d0d0;
		}

		.table tr td {
			border: 1px solid #d0d0d0;
			padding: 15px;
		}

		.text-right {
			text-align: right;
			padding: 10px;
		}
	</style>
</head>
<body>
	<div>
		<?php
			if (!empty($_GET['finder'])) {
				$pieces = explode(' ', $_POST['data']);
				foreach($pieces as $index => $piece) {
					$query = "SELECT * FROM phrases WHERE phrase = '".$piece."'";

					$result = $mysqli->query($query);
					$phrase_row = $result->fetch_row();
				}
				
			}
		?>
	</div>
	<br><br>
	<form action="/?show-sequences=1" method="POST">
		<table>
			<tr>
				<td class="text-right">Зберегти одну або декілька фраз розділяючи по "пробілу"</td>
				<td><input type="text" name="data" value="<?php echo !empty($_POST['data']) ? $_POST['data'] : ''; ?>"></td>
			</tr>
			<tr>
				<td class="text-right"><label for="is_sequence">Оцінка автора: "це повноцінне висловлювання"</label></td>
				<td><input type="checkbox" name="is_sequence" id="is_sequence" <?php echo isset($_POST['is_sequence']) ? 'checked' : '';?>> </td>
			</tr>
			<tr>
				<td class="text-right">Це висловлювання є альтернативним варіантом для id</td>
				<td><input type="text" name="alternative_id" value="<?php echo !empty($_POST['alternative_id']) ? $_POST['alternative_id'] : '0'; ?>"></td>
			</tr>
			<tr>
				<td class="text-right">Це висловлювання доповнює групу сформовану на основі з id</td>
				<td><input type="text" name="tag_id" value="<?php echo !empty($_POST['tag_id']) ? $_POST['tag_id'] : '0'; ?>"></td>
			</tr>
			<tr>
				<td class="text-right">Кнопка для збереження</td>
				<td><input type="submit" name="submit"></td>
			</tr>
		</table> 
	</form>
	<br><br>
	<!-- <form action="/" method="GET">
		Знайти найбільш відповідну послідовність
		<input type="text" name="finder">
		<input type="submit" name="submit">
	</form> -->
	<table class="table">
		<tr>
			<td>Висловлювання</td>
			<!-- <td>Альтернативні вирази</td> -->
			<!-- <td>Збережені продовження</td> -->
		</tr>

		<?php 
		

		$continue_getting_starts = true;
		$offset = 0;

		while ($continue_getting_starts) {
			$query = "SELECT * FROM sequences WHERE before_current_ending_id = 0 and phrase_id != 0 LIMIT 100 OFFSET ".$offset;
			$offset = $offset + 100;
			$processed_starter = false;
			$result = $mysqli->query($query);
			$sequences_rows = $result->fetch_all(MYSQLI_ASSOC);
			foreach ($sequences_rows as $sequences_row) {
				$row_html = '<tr><td>';
				$processed_starter = true;

				$query = "SELECT * FROM phrases WHERE id = ".$sequences_row['phrase_id'];
				$phrase_result = $mysqli->query($query);
				$phrase_row = $phrase_result->fetch_assoc();
				/*$row_html = $row_html . $phrase_row['phrase'];
				$row_html = $row_html . ' ('.$sequences_row['id'].') ';*/
				$row_html = $row_html . '<pre>{<br>';
				$row_html = $row_html . '    \'id\': '.$sequences_row['id'].',<br>';
				$row_html = $row_html . '    \'Текст\': \'</pre>'.$phrase_row['phrase'].'<pre>\',<br>';

				$query = "SELECT * FROM alternatives WHERE sequence_id = ".$sequences_row['id'];
				$result = $mysqli->query($query);
				$alternatives_rows = $result->fetch_all(MYSQLI_ASSOC);
				if (!empty($alternatives_rows)) {
					$row_html = $row_html . '    \'Перелік варіантів альтернативних висловлювань\': [<br>';
					foreach ($alternatives_rows as $alternatives_row) {
						$full_sequence_array = getFullSequenceArray($alternatives_row['alternative_sequence_id'], $mysqli);

						$row_html = $row_html . '            \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';
					}
					$row_html = $row_html . '    ]<br>';
				} else {
					$row_html = $row_html . '    \'Перелік варіантів альтернативних висловлювань\': [],<br>';
				}

				$query = "SELECT * FROM tags WHERE sequence_id = ".$sequences_row['id'];
				$result = $mysqli->query($query);
				$tags_rows = $result->fetch_all(MYSQLI_ASSOC);
				if (!empty($tags_rows)) {
					$row_html = $row_html . '    \'Перелік висловлювань з групи до якої належить дане висловлювання \': [<br>';
					foreach ($tags_rows as $tag_rows) {
						$full_sequence_array = getFullSequenceArray($tag_rows['tag_id'], $mysqli);

						$row_html = $row_html . '            \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';
					}
					$row_html = $row_html . '    ],<br>';
				} else {
					$row_html = $row_html . '    \'Перелік висловлювань з групи до якої належить дане висловлювання \': [],<br>';
				}
				//$row_html = $row_html . '}<br>';
				//$row_html = $row_html . '</td><td>';

				$query = "SELECT * FROM sequences_equations WHERE sequence_all_data_from_id = ".$sequences_row['id'];
				$continue_sequences_equations_result = $mysqli->query($query);
				$continue_sequences_equations = $continue_sequences_equations_result->fetch_all(MYSQLI_ASSOC);

				if (!empty($continue_sequences_equations)) {

					$row_html = $row_html . '    \'Перелік збережених продовжень висловлювання\': [<br>';
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

						$continue_phrase_text = $phrase_row['phrase'].' ';
						$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$continue_sequences_equation['equate_to_record_id'];
						$continue_sequences_result = $mysqli->query($query);
						$continue_sequences = $continue_sequences_result->fetch_assoc();
						if (!empty($continue_sequences)) {

							$query = "SELECT * FROM phrases WHERE id = ".$continue_sequences['phrase_id'];
							$continue_phrase_result = $mysqli->query($query);
							$continue_phrase = $continue_phrase_result->fetch_assoc();
							$continue_phrase_text = $continue_phrase_text . $continue_phrase['phrase'].' ';

							$has_continue = true;
							while ($has_continue) {
								$query = "SELECT * FROM sequences WHERE before_current_ending_id = ".$continue_sequences['id'];
								$continue_sequences_result = $mysqli->query($query);
								$continue_sequences = $continue_sequences_result->fetch_assoc();
								if (!empty($continue_sequences)) {
									$query = "SELECT * FROM phrases WHERE id = ".$continue_sequences['phrase_id'];
									$continue_phrase_result = $mysqli->query($query);
									$continue_phrase = $continue_phrase_result->fetch_assoc();
									
									$continue_phrase_text = $continue_phrase_text . $continue_phrase['phrase'].' ';
								} else {
									$row_html = $row_html . '            {<br>';
									$row_html = $row_html . '                \'id\': \''.$continue_sequences_equation['equate_to_record_id'].'\',<br>';
									$row_html = $row_html . '                \'Текст\': \'</pre>'.$continue_phrase_text.'<pre>\',<br>';
									if (count($alternatives) > 0) {
										$row_html = $row_html . '                \'Перелік варіантів альтернативних висловлювань\': [<br>';
										foreach ($alternatives as $alternative) {
											$row_html = $row_html . $alternative;
										}
										$row_html = $row_html . '                ]<br>';
									} else {
										$row_html = $row_html . '                \'Перелік варіантів альтернативних висловлювань\': [],<br>';
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

									$row_html = $row_html . '            },<br>';
									$has_continue = false;
								}
							}
						} else {
							$alternatives_text = implode(' ', $alternatives);
							$row_html = $row_html . '                \'id\': \''.$continue_sequences_equation['equate_to_record_id'].'\',<br>';
							$row_html = $row_html . '                \'Висловлювання яке визначає процес\': \'</pre>'.$continue_phrase_text.'<pre>\'<br>';
							if (count($alternatives) > 0) {
								$row_html = $row_html . '                \'Перелік варіантів альтернативних висловлювань\': [<br>';
								foreach ($alternatives as $alternative) {
									$row_html = $row_html . $alternative;
								}
								$row_html = $row_html . '                ]<br>';
							} else {
								$row_html = $row_html . '                \'Перелік варіантів альтернативних висловлювань\': []<br>';
							}

							if (count($tags_rows) > 0) {
									$row_html = $row_html . '                \'Перелік висловлювань з групи до якої належить дане висловлювання\': [<br>';
									foreach ($tags as $tag) {
										$row_html = $row_html . $tag;
									}
									$row_html = $row_html . '                ],<br>';
								} else {
									$row_html = $row_html . '                \'Перелік висловлювань з групи до якої належить дане висловлювання\': []<br>';
								}
								$row_html = $row_html . '            }<br>';
							}

						$continue_phrase_text = $continue_phrase_text.' ';

						getAllEquationContinue($row_html, $continue_sequences_equation['equate_to_record_id'], $continue_phrase_text, $mysqli);
					}

					$row_html = $row_html . '    ]<br>';
				} else {
					$row_html = $row_html . '    \'Перелік збережених продовжень Висловлювання яке визначає процесу\': []<br>';
				}

				$row_html = $row_html . '}</td></tr>';
				echo $row_html;
			}

			if (!$processed_starter) {
				$continue_getting_starts = false;
			}
		}

		?>
	</table>
	<br>
	Таблиця фраз
	<table>
		<tr>
			<td>Ідентифікатор</td>
			<td>Фраза</td>
		</tr>
	</table>
</body>
</html>

<?php 
} else {

if(!empty($_GET['delete-process']))
{
	//
}

if(!empty($_GET['delete-pelation']))
{
	//
}

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Процеси</title>
	<style type="text/css">
		pre {
			display: inline;
		}

		.table {
			border: 1px solid #d0d0d0;
		}

		.table tr td {
			border: 1px solid #d0d0d0;
			padding: 15px;
		}

		.text-right {
			text-align: right;
			padding: 10px;
		}
	</style>
</head>
<body>
	<br><br>
	<form action="/" method="POST">
		<table>
			<tr>
				<td class="text-right">Висловлювання що визначає процес</td>
				<td><input type="text" name="data" value="<?php echo !empty($_POST['data']) ? $_POST['data'] : ''; ?>"></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td class="text-right"><label for="is_sequence">Оцінка автора: "це висловлювання є простим для розуміння визначенням"</label></td>
				<td><input type="checkbox" name="is_sequence" id="is_sequence" <?php echo isset($_POST['is_sequence']) ? 'checked' : '';?>> </td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td class="text-right">Є альтернативним варіантом визначення під id</td>
				<td><input type="text" name="alternative_id" value="<?php echo !empty($_POST['alternative_id']) ? $_POST['alternative_id'] : '0'; ?>"></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td class="text-right">Є визначенням процесу який <br>являє собою підпроцес для id</td>
				<td><input type="text" name="parent_process_id" value="<?php echo !empty($_POST['parent_process_id']) ? $_POST['parent_process_id'] : '0'; ?>"></td>
				<td class="text-right">Є підпроцесом який виконується після id</td>
				<td><input type="text" name="goes_after_process_id" value="<?php echo !empty($_POST['goes_after_process_id']) ? $_POST['goes_after_process_id'] : '0'; ?>"></td>
			</tr>
			<tr>
				<td class="text-right">Кнопка для збереження</td>
				<td><input type="submit" name="submit"></td>
				<td></td>
				<td></td>
			</tr>
		</table> 
	</form>
	<br><br>
	<!-- <form action="/" method="GET">
		Знайти найбільш відповідну послідовність
		<input type="text" name="finder">
		<input type="submit" name="submit">
	</form> -->
	<table class="table">
		<tr>
			<td>json об'єкти визначення процесів</td>

			<?php
				$continue_getting_starts = true;
				$offset = 0;

				while ($continue_getting_starts) {
					$query = "SELECT * FROM processes LIMIT 100 OFFSET ".$offset;
					$offset = $offset + 100;
					$processed_starter = false;
					$result = $mysqli->query($query);
					$processes_rows = $result->fetch_all(MYSQLI_ASSOC);
					
					foreach ($processes_rows as $processes_row) {
						$processed_starter = true;
						$row_html = '<tr><td>';

						$row_html = $row_html . '<pre>{<br>';
						$row_html = $row_html . '    \'id\': '.$processes_row['id'].',<br>';
						$full_sequence_array = getFullSequenceArray($processes_row['sequence_id'], $mysqli);
						$row_html = $row_html . '    \'Визначення\': \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';

						$query = "SELECT * FROM alternatives WHERE sequence_id = ".$processes_row['sequence_id'];
						$result = $mysqli->query($query);
						$alternatives_rows = $result->fetch_all(MYSQLI_ASSOC);
						if (!empty($alternatives_rows)) {
							$row_html = $row_html . '    \'Перелік варіантів альтернативних визначень\': [<br>';
							foreach ($alternatives_rows as $alternatives_row) {
								$full_sequence_array = getFullSequenceArray($alternatives_row['alternative_sequence_id'], $mysqli);

								$row_html = $row_html . '            \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';
							}
							$row_html = $row_html . '    ],<br>';
						} else {
							$row_html = $row_html . '    \'Перелік варіантів альтернативних визначень\': [],<br>';
						}

						$query = "SELECT * FROM processes_relations WHERE goes_after_process_id = 0 and parent_process_id = ".$processes_row['id'];
						$result = $mysqli->query($query);
						$relations_rows = $result->fetch_all(MYSQLI_ASSOC);

						if (!empty($relations_rows)) {
							$row_html = $row_html . '    \'Перелік визначень дочірних процесів\': [<br>';
							foreach ($relations_rows as $relation_row) {
								$query = "SELECT * FROM processes WHERE id = ".$relation_row['process_id'];
								$result = $mysqli->query($query);
								$subprocess =  $result->fetch_assoc();

								$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

								$row_html = $row_html . '            {<br>';
								$row_html = $row_html . '                \'id\': \'</pre>'.$subprocess['id'].'<pre>\',<br>';
								$row_html = $row_html . '                \'Визначення\': \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';

								$query = "SELECT * FROM processes_relations WHERE goes_after_process_id = ".$subprocess['id']." and parent_process_id = ".$processes_row['id'];
								$result = $mysqli->query($query);
								$subrelations_rows = $result->fetch_all(MYSQLI_ASSOC);

								if (!empty($subrelations_rows)) {
									$row_html = $row_html . '                \'Наступним виконується\': [<br>';
									foreach ($subrelations_rows as $subrelation_row) {
										$query = "SELECT * FROM processes WHERE id = ".$subrelation_row['process_id'];
										$result = $mysqli->query($query);
										$subprocess =  $result->fetch_assoc();

										$full_sequence_array = getFullSequenceArray($subprocess['sequence_id'], $mysqli);

										$row_html = $row_html . '                    {<br>';
										$row_html = $row_html . '                        \'id\': \'</pre>'.$subprocess['id'].'<pre>\',<br>';
										$row_html = $row_html . '                        \'Визначення\': \'</pre>'.implode(' ', $full_sequence_array).'<pre>\',<br>';

										getSubprocesses($row_html, 1, $subprocess, $processes_row, $mysqli);

										$row_html = $row_html . '                    }<br>';
									}
									$row_html = $row_html . '                ]<br>';
								} else {
									$row_html = $row_html . '                \'Наступним виконується\': []<br>';
								}
								
								
								$row_html = $row_html . '            }<br>';
							}
							$row_html = $row_html . '    ],<br>';
						} else {
							$row_html = $row_html . '    \'Перелік визначень дочірних процесів\': [],<br>';
						}

						$row_html = $row_html . '}</pre><br>';

						$row_html = $row_html . '</td></tr>';
						echo $row_html;
					}

					if (!$processed_starter) {
						$continue_getting_starts = false;
					}
				}
			?>

			<!-- <td>Альтернативні вирази</td> -->
			<!-- <td>Збережені продовження</td> -->
		</tr>
	</table>
</body>
</html>


<?php
}
?>