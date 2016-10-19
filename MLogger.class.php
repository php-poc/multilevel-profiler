<?php

class MLogEntry
{
	const SHOW_NONE = 0;
	const SHOW_TIME = 1;
	const SHOW_DURATION = 2;
	const SHOW_TOTAL_DURATION = 4;
	const SHOW_PARAMS = 8;
	const SILENT_END = 16;
	const SILENT_BEGIN = 32;
	const SHOW_MEMORY_USAGE = 64;
	const ECHO_SCREEN = 128;

	const TYPE_BEGIN = 1;
	const TYPE_END = 2;
	const TYPE_REPORT_STATUS = 3;

	public $title;
	public $message;
	public $extra_details;
	public $flags = null;
	public $begin_time = null;
	public $entry_time;
	public $entry_memory;


	/** @var  MLogEntry */
	public $parent;

	/** @var MLogEntry[] */
	public $children = null;

	protected $count = 0;
	protected $level = 0;
	protected $logEntryOpen = false;
	protected $freezedFlags = array();

	function __construct($title, $message, $additional_params = null, $flags = null, $level = 0)
	{
		$this->title = $title;
		$this->message = $message;
		$this->extra_details = $additional_params;
		$this->flags = $flags;
		$this->entry_time = microtime(true);
		$this->entry_memory = memory_get_usage();
		$this->level = $level;
		$this->count = 1;
	}

	function isOpen()
	{
		return $this->logEntryOpen;
	}

	function next(MLogEntry $child)
	{
		$child->level = $this->level + 1;
		$child->count = count($this->children) + 1;
		$this->children[] = &$child;

		return $child;
	}

	function prev()
	{
		return $this->parent;
	}

	function freezeFlags($removeFlags = null, $addFlags = null)
	{
		array_push($this->freezedFlags, $this->flags);
		$this->setFlags($removeFlags, $addFlags);

		return $this;
	}

	function unfreezeFlags(){
		$this->flags = array_pop($this->freezedFlags);
	}

	function log($type, $additional_parameters = null)
	{
		$current_memory = memory_get_usage();

		if ($type == self::TYPE_BEGIN)
		{
			$this->logEntryOpen = true;
		}

		if ($type == self::TYPE_END)
		{
			$this->logEntryOpen = false;
		}

		if(
			$type != self::TYPE_REPORT_STATUS &&
			(
				($this->flags & self::SILENT_BEGIN && $type == self::TYPE_BEGIN) ||
				($this->flags & self::SILENT_END && $type == self::TYPE_END)
			)

		)
		{
			$message = null;
		}
		else
		{
			$details = array();
			$message = '{$indent}{$count} -{$time} {$type_text}{$title}{$message}{$params}{$duration}{$total_duration}{$memory_usage}';

			$details["indent"] = '';

			if($this->level)
			{
				$details["indent"] = str_repeat("   ", $this->level - 1);
				$details["count"] = $this->count;
			}
			else
			{
				$details["count"] = '0';
			}
			$details["indent"] .= '> ';

			if($this->flags & self::SHOW_TIME)
			{
				$details["time"] = date('Y-m-d H:i:s', $this->entry_time);
				$details["time"] = " [{$details["time"]}]";
			}

			$details["title"] = $this->title;
			$details["message"] = $this->message;

			if($this->title && $this->message)
			{
				$details["title"] .= ': ';
			}

			$details["type_text"] = ($type == MLogEntry::TYPE_BEGIN) ? "Beginning": ($type == MLogEntry::TYPE_END ? "Ending": "");

			if(!$this->logEntryOpen)
			{
				if($this->flags & self::SHOW_DURATION)
				{
					$details["duration"] = round(microtime(true) - $this->entry_time, 5);
					$details["duration"] = " |=> TIME.Step: {$details["duration"]}";
				}

				if($this->flags & self::SHOW_TOTAL_DURATION)
				{
					$details["total_duration"] = round(microtime(true) - $this->begin_time, 5);
					$details["total_duration"] = " TIME.Total: {$details["total_duration"]}";
				}

				if($details["duration"] && $details["total_duration"])
				{
					$details["duration"] .= "; ";
				}

				if($this->flags & self::SHOW_MEMORY_USAGE)
				{
					$details["delta_memory"] = $this->formatMemory($current_memory - $this->entry_memory);
				}

				if($this->flags & self::SILENT_BEGIN)
				{
					$details["begin_memory"] = $this->formatMemory($this->entry_memory);
				}
			}
			elseif($this->logEntryOpen)
			{
				if($this->flags & self::SHOW_PARAMS && !is_null($this->extra_details))
				{
					$type = gettype($this->extra_details);
					$details["params"] = json_encode($this->extra_details);
					$details["params"] = " ([{$type}]-{$details["params"]}) ";
				}

				if($this->flags & self::SHOW_MEMORY_USAGE)
				{
					$details["current_memory"] = $this->formatMemory($this->entry_memory);
				}
			}

			if($additional_parameters && $this->flags & self::SHOW_PARAMS)
			{
				$details["params"] .= " - [Additional Logs: ".json_encode($additional_parameters)."]";
			}

			if($this->flags & (self::SILENT_BEGIN | self::SILENT_END))
			{
				$details["type_text"] = "";
			}
			else
			{
				$details["type_text"] .= " ";
			}

			if($this->flags & self::SHOW_MEMORY_USAGE)
			{
				$details["memory_usage"] = '';
				$mem_sep = ' ||==>';

				if($details["begin_memory"])
				{
					$details["memory_usage"] .= $mem_sep." MEM.BlockBegin: {$details["begin_memory"]}";
					$mem_sep = "; ";
				}

				if($details["delta_memory"])
				{
					$details["memory_usage"] .= $mem_sep." MEM.Delta: {$details["delta_memory"]}";
					$mem_sep = "; ";
				}

				if($details["current_memory"])
				{
					$details["memory_usage"] .= $mem_sep." MEM.Curr: {$details["current_memory"]}";
				}
			}

			$message = $this->_print($message, $details);
		}

		if ($this->flags & self::ECHO_SCREEN)
		{
			echo $message;
		}

		return $message;
	}

	protected function formatMemory($value)
	{
		$chunks = array();
		$suffixes = 'BKMGTPE';

		$negative = false;

		if($value < 0)
		{
			$negative = true;
			$value = abs($value);
		}

		while($value)
		{
			$chunks[] = $value % 1024;
			$value = (int)($value / 1024);
		}

		$count_chunks = count($chunks);

		if($count_chunks < 2)
		{
			$chunk_suffixes = $suffixes[0];
		}
		else
		{
			$chunk_suffixes = substr($suffixes, $count_chunks - 2, 2);
			$chunk_suffixes = strrev($chunk_suffixes);
		}
		$chunks = array_reverse($chunks);
		$chunks = array_slice($chunks, 0, 2);

		$output = '';
		$counter = 0;
		foreach($chunks as $chunk)
		{
			$output .= $chunk.$chunk_suffixes[$counter++];
		}

		if($negative)
		{
			$output = '-'.$output;
		}

		return $output;
	}

	protected function _print($__format, $__variables)
	{
		extract($__variables);

		return eval(" return \"{$__format}\";");
	}

	public function getLastMessage()
	{
		if ($this->children)
		{
			$message = $this->children[count($this->children)-1]->getLastMessage();
		}
		else
		{
			$this->freezeFlags(MLogEntry::ECHO_SCREEN);
			$message = $this->log(MLogEntry::TYPE_REPORT_STATUS);
			$this->unfreezeFlags();
		}

		return $message;
	}

	function setFlags($removeFlags, $addFlags)
	{
		if ($removeFlags)
		{
			$this->flags &= $removeFlags;
		}

		if ($addFlags)
		{
			$this->flags |= $addFlags;
		}
	}
}

class MLogger
{
	const LEVEL_PROD = 0;
	const LEVEL_DEBUG = 1;
	const LEVEL_INFO = 2;
	public $log_level;
	protected $log_dir;
	protected $log_file;
	/** @var MLogEntry */
	protected $first = null;

	/** @var MLogEntry */
	protected $last = null;

	function __construct($dir, $file = null, $level = MLogger::LEVEL_PROD)
	{
		$dir_exists = false;

		if(!is_dir($dir))
		{
			if(mkdir($dir))
			{
				$dir_exists = true;
			}
		}
		else
		{
			$dir_exists = true;
		}

		if(!$dir_exists)
		{
			echo "Log directory '{$dir}' not found or cannot be created";
			exit();
		}

		$this->log_dir = $dir;

		if(!$file)
		{
			$file = date('Ymd-His').'.log';
		}

		$this->log_file = $file;

		file_put_contents($this->filePath(), "");

		$this->log_level = $level;

		$this->begin(
			"Logging Initiated", null, null, /*MLogEntry::SILENT_BEGIN|*/
			MLogEntry::SILENT_END
		);
	}

	function filePath()
	{
		return $this->log_dir.'/'.$this->log_file;
	}

	function getLastMessage()
	{
		$message = $this->last->getLastMessage();

		return $message;
	}

	function setLogLevel($level){
		$prev = $this->log_level;
		$this->log_level = $level;
		return $prev;
	}

	function begin($log_title, $log_message = '', $log_params = null, $flags = null)
	{
		if(is_null($flags))
		{
			$flags = MLogEntry::SHOW_NONE;
		}

		switch($this->log_level)
		{
		case MLogger::LEVEL_PROD:
			break;

		case MLogger::LEVEL_DEBUG:
			$flags |= MLogEntry::SHOW_DURATION | MLogEntry::SHOW_TOTAL_DURATION | MLogEntry::SHOW_PARAMS | MLogEntry::SHOW_MEMORY_USAGE;
			break;

		case MLogger::LEVEL_INFO:
			$flags |= MLogEntry::SHOW_DURATION;
			break;
		}

		$flags |= MLogEntry::SHOW_TIME;

		$new = new MLogEntry($log_title, $log_message, $log_params, $flags);
		$new->parent = $this->last;

		if($this->last)
		{
			$this->last = $this->last->next($new);
		}
		else
		{
			$this->last = &$new;
		}

		if(!$this->first)
		{
			$this->first = $this->last;
		}

		$this->last->begin_time = $this->first->entry_time;

		$this->log($this->last->log(MLogEntry::TYPE_BEGIN));

		return $this;
	}

	function log($message)
	{
		if(!is_null($message))
		{
			file_put_contents($this->filePath(), $message.PHP_EOL, FILE_APPEND);
		}

		return $this;
	}

	function rename($newFileName, $separator = null)
	{
		if($newFileName != $this->log_file)
		{
			$oldPath = $this->filePath();
			$this->log_file = $newFileName;

			if(is_file($oldPath))
			{
				$contents = file_get_contents($oldPath);
				unlink($oldPath);

				if(is_file($this->filePath()))
				{
					$contents = $separator.$contents;
				}
				file_put_contents($this->filePath(), $contents, FILE_APPEND);
			}

			$this->begin("Renaming Log File from {$oldPath} to {$this->filePath()}", null, null, MLogEntry::SILENT_BEGIN)->finish();
		}
	}

	function finish($additional_parameters = null)
	{
		if($this->last)
		{
			$this->log($this->last->log(MLogEntry::TYPE_END, $additional_parameters));
			$this->last = $this->last->prev();
		}

		return $this;
	}

	function __destruct()
	{
		while($this->last->parent)
		{
			$this->finish("WARNING: FORCED DESTRUCTION!");
		}
	}

	public function setFlag($removeFlags = null, $addFlags = null)
	{
		$this->last->setFlags($removeFlags, $addFlags);
	}
}
