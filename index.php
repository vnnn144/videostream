<?php include('videostream.php') ?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<meta name="author" content="Until Die" />
		<title>Video Stream</title>
		<style type="text/css">
			#video_background {
				position: absolute;
				bottom: 0px;
				right: 0px;
				min-width: 100%;
				min-height: 100%;
				width: auto;
				height: auto;
				z-index: -1000;
				overflow: hidden;
			}
		</style>
	</head>
	<body>
		<video id="video_background" preload="auto" autoplay="true" loop="loop" muted>
			<source src="<?php $stream->create('stream/How_fast.ogg', 500) ?>" type="video/ogg">
			Your browser does not support the video tag.
		</video>
	</body>
</html>