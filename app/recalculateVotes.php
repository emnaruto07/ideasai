<?
	
	loadDbs(array('gpt3ideas','gpt3votes'));

	$query=$gpt3ideasDb->prepare("SELECT * FROM gpt3ideas WHERE human_seeded IS NOT 1 ORDER BY votes DESC");
	$query->execute();
	$ideas=$query->fetchAll(PDO::FETCH_ASSOC);

	foreach($ideas as $idea) {
		$query=$gpt3votesDb->prepare("SELECT COUNT(*) FROM gpt3votes WHERE id=:id AND upvote=1");
		$query->bindValue(':id',$idea['id']);
		$query->execute();
		$upvotes=$query->fetchAll(PDO::FETCH_ASSOC)[0]['COUNT(*)'];

		$query=$gpt3votesDb->prepare("SELECT COUNT(*) FROM gpt3votes WHERE id=:id AND downvote=1");
		$query->bindValue(':id',$idea['id']);
		$query->execute();
		$downvotes=$query->fetchAll(PDO::FETCH_ASSOC)[0]['COUNT(*)'];

		$query=$gpt3ideasDb->prepare("UPDATE gpt3ideas SET votes=:votes,upvotes=:upvotes,downvotes=:downvotes WHERE id=:id");
		$query->bindValue(':id',$idea['id']);
		$query->bindValue(':votes',($upvotes-$downvotes));
		$query->bindValue(':upvotes',$upvotes);
		$query->bindValue(':downvotes',$downvotes);
		$query->execute();

		echo $idea['idea'];
		echo "\n";
		echo ($upvotes-$downvotes);
		echo ' | ';
		echo $idea['votes'];
		echo "\n";
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