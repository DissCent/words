<?php

require_once 'WavFile.php';

$fname = '/tmp/in.wav';
$file = new WavFile($fname);

$max_time_idle = 0.7; // without breaking a word
$min_time_word = 0.1;
$idle = 0.01 * 32768; // everything below is silence

echo "Opened file: $fname\n";
echo "    Sample rate : {$file->getSampleRate()}\n";
echo "    Channels    : {$file->getNumChannels()}\n";
echo "    Frames      : {$file->getNumBlocks()}\n";
echo "    Format      : {$file->getAudioFormat()}\n";

if ($file->getAudioFormat() != WavFile::WAVE_FORMAT_PCM || $file->getBitsPerSample() != 16) {
	die("Invalid audio format.\n");
}

$frame = [0, 0];
$max_frames_idle = $max_time_idle * $file->getSampleRate();
$min_frames_word = $min_time_word * $file->getSampleRate();

$cur_frames_word = 0;
$words = 0;
$last_word_start = 0;
$last_word_end = 0;
$idle_count = 0; // frames idle after last word

$tell = 0;
$samples = $file->getSamples();
$max = $file->getNumBlocks();

$cur_word_start = 0;

for ($i = 0; $i < $file->getNumBlocks(); ++$i) {
	if ($tell == $max) {
		die("Not enough data to read {$file->getNumBlocks()} frames, aborting.");
	}

	$frame[0] = unpack('s', substr($samples, $tell * 4, 2))[1];
	$frame[1] = unpack('s', substr($samples, $tell * 4 + 2, 2))[1];

	++$tell;

	$this_frame_is_word = ($frame[0] > $idle || $frame[0] < -$idle || $frame[1] > $idle || $frame[1] < -$idle);

	if ($this_frame_is_word) {
		$cur_max_frames_idle = $max_frames_idle;

		if ($idle_count > $cur_max_frames_idle) {
			if ($cur_frames_word > $min_frames_word) {
				printf("word %d: %f (%f -> %f) seconds word at %d (found at %d)\n",
					$words, ($last_word_end - $last_word_start) / $file->getSampleRate(),
					$last_word_start / $file->getSampleRate(),
					$last_word_end / $file->getSampleRate(),
					($tell - $idle_count) / $file->getSampleRate(), $tell / $file->getSampleRate());

				echo "frames: $cur_frames_word, $max_frames_idle, idle_count: $idle_count\n";

				$pathname = sprintf("/tmp/words/%03d.wav", $words);

				$outfile = new WavFile($file->getNumChannels(), $file->getSampleRate(), 16);
				$outfile->setSamples(substr($samples, $cur_word_start, ($i * 4 - $cur_word_start)));
				$outfile->save($pathname);

				unset($outfile);

				$cur_word_start = $i * 4;

				++$words;
			} else {
				echo "only " . ($cur_frames_word / $file->getSampleRate()) . " seconds word at " . ($tell / file->getSampleRate()) . "\n";
			}

			$cur_frames_word = 0;
			$last_word_start = $tell;
		} else {
			$last_word_end = $tell;
		}

		++$cur_frames_word;
		$idle_count = 0;
	} else {
		++$idle_count;
	}
}

echo "$words words\n";
echo "Done.\n";
