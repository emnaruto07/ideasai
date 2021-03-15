<?
	
	loadDbs(array('gpt3ideas','gpt3votes'));

	$query=$gpt3ideasDb->prepare("SELECT * FROM gpt3ideas WHERE human_seeded IS NOT 1 ORDER BY votes DESC");
	$query->execute();
	$ideas=$query->fetchAll(PDO::FETCH_ASSOC);

	foreach($ideas as $idea) {
		$query=$gpt3votesDb->prepare("SELECT * FROM gpt3votes WHERE id=:id");
		$query->bindValue(':id',$idea['id']);
		$query->execute();
		$votes=$query->fetchAll(PDO::FETCH_ASSOC);

		echo $idea['idea'];
		echo "\n\n";

		foreach($votes as $vote) {
			$query=$gpt3votesDb->prepare("SELECT * FROM gpt3votes WHERE (ip=:ip OR user_id=:user_id) AND id=:id");
			$query->bindValue(':ip',$vote['ip']);
			$query->bindValue(':user_id',$vote['user_id']);
			$query->bindValue(':id',$vote['id']);
			$query->execute();
			$duplicates=$query->fetchAll(PDO::FETCH_ASSOC);

			echo 'Vote: '.$vote['ip'];
			echo "\n\n";
			
			$i=1;
			foreach($duplicates as $dupe) {
				if($i==1) {
					// always keep 1 of the votes, delete rest
					$i++;
					continue;
				}
				// delete the other duplicate votes

				echo 'Deleting duplicate vote for '.$dupe['id'].' by '.$dupe['ip'];
				echo "\n";
				$query=$gpt3votesDb->prepare("DELETE FROM gpt3votes WHERE ip=:ip AND id=:id LIMIT 1");
				$query->bindValue(':ip',$dupe['ip']);
				$query->bindValue(':id',$dupe['id']);
				$query->execute();
				$i++;
			}
		}
	}


	function loadDbs($dbs) {
		try {
			foreach($dbs as $db) {
				global ${$db.'Db'};

				// <load cities db>
					${$db.'DbFile'}=__DIR__.'/../data/'.$db.'.db';
					if(!file_exists(${$db.'DbFile'})) {
						echo ${$db.'DbFile'};
						echo ' does not exist';
					}
					// if old undeleted journal file found, delete it because it locks the db for writing
					if(file_exists(${$db.'DbFile'}.'-journal') && filemtime(${$db.'DbFile'}.'-journal')<strtotime("-5 minutes")) {
						rename(${$db.'DbFile'}.'-journal',${$db.'DbFile'}.'-journal_'.date('Y-m-d-H-i-s'));
					}
					${$db.'Db'} = new PDO('sqlite:/'.${$db.'DbFile'}) or die("Cannot open the database");
					${$db.'Db'}->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
					${$db.'Db'}->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					// echo "\n\n";
					// echo $db.'Db';
					// echo "\n\n";
					// print_r(${$db.'Db'});
					// echo "\n\n";
				// </load cities db>
			}
		}
		catch ( PDOException $e ) {
	        echo 'ERROR!';
	        print_r( $e );
	    }
	}

?>