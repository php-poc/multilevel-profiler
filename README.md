# multilevel-profiler
Hierarchical performance/ progress profiler/ logger

# Description
This PoC class allows developers and users to create a text-based tree-like log, which writes how much time and memory each section of code needs and it logs information in a hierarchical manner, so its users can see how much resources each subsction of their code needs.

The class can also be used as a production logging system. There are predefined modes, which allow users to select which information they want to have shown in their logs.

An example Output is presented here:
```
> 0 - [2016-10-10 15:58:26] Logging Initiated ||==> MEM.Curr: 783K648B
> 1 - [2016-10-10 15:58:26] Initiate Sync Phase `1` |=> TIME.Step: 0.026;  TIME.Total: 0.144 ||==> MEM.BlockBegin: 2M698K;  MEM.Delta: 5K504B
> 2 - [2016-10-10 15:58:26] Beginning Cleaning outdated Sync Info ||==> MEM.Curr: 2M705K
   > 1 - [2016-10-10 15:58:26] Cleaning outdated Sync Info: Sync ID: 5 |=> TIME.Step: 1.611;  TIME.Total: 1.779 ||==> MEM.BlockBegin: 2M708K;  MEM.Delta: 3K952B
> 2 - [2016-10-10 15:58:26] Ending Cleaning outdated Sync Info |=> TIME.Step: 1.618;  TIME.Total: 1.781 ||==> MEM.Delta: 6K872B
> 3 - [2016-10-10 15:58:33] Beginning Read Some Details ||==> MEM.Curr: 6M660K
   > 1 - [2016-10-10 15:58:33] Read Type1 details |=> TIME.Step: 134.328;  TIME.Total: 141.694 ||==> MEM.BlockBegin: 6M662K;  MEM.Delta: 88M253K
   > 2 - [2016-10-10 16:00:48] Read Type2 details |=> TIME.Step: 0.321;  TIME.Total: 142.018 ||==> MEM.BlockBegin: 94M916K;  MEM.Delta: -6M370K
   > 3 - [2016-10-10 16:00:48] Read Type3 details |=> TIME.Step: 21.069;  TIME.Total: 163.088 ||==> MEM.BlockBegin: 88M547K;  MEM.Delta: 14M104K
> 3 - [2016-10-10 15:58:33] Ending Read Some Details |=> TIME.Step: 155.726;  TIME.Total: 163.091 ||==> MEM.Delta: 95M1014K
> 4 - [2016-10-10 16:01:30] Beginning Compare Details ||==> MEM.Curr: 139M297K
   > 1 - [2016-10-10 16:01:30] Beginning Details' Match ||==> MEM.Curr: 139M298K
      > 1 - [2016-10-10 16:01:30] Beginning Comparing Type1 and Type2 ||==> MEM.Curr: 139M312K
         > 1 - [2016-10-10 16:01:30] Finding Type1|Type2 matches |=> TIME.Step: 2.422;  TIME.Total: 186.127 ||==> MEM.BlockBegin: 139M313K;  MEM.Delta: 674K960B
         ...
> 4 - [2016-10-10 16:01:30] Ending Compare Details |=> TIME.Step: 628.632;  TIME.Total: 812.331 ||==> MEM.Delta: 1M106K
```

## Note 1:
As this PoC began its life as a pure logger, I named it MLogger For Multi(L)evelLogger, might change its name sooner or later to [Something]Profiler as soon as I find a better name for it! :D

## Note 2:
Code Documentation is missing! :'( sorry, had no time to really annotate the code, would do this if I ever had time.

# Requirements
well, I can say it's working with PHP 5.3 (and I guess newer), cannot definitely tell if it works on older versions, have honestly not analysed it on older versions. 

# Installation
Just copy the file and include it!

# Configuration
Just use something like 

```PHP
/**
 * @var $log_directory // string 
 * @var $log_file_name // string|null (for automatic date('Ymd-His').'.log' as name)
 * @var $level         // MLogger::LEVEL_* (LEVEL_PROD, LEVEL_DEBUG, LEVEL_INFO) for the detail level
 */
$logger = new MLogger(dirname(__FILE__).'/logs/', null, MLogger::LEVEL_DEBUG);
```

and you have an instance to use.

## Note:
It overrides the previous log file if the file exists.

# Usage

Each logging Level begins with a ```begin()``` function:

```PHP
/**
 * @var $log_title   string - Action Title (initially intended for an "action ID" kind of thing: ReadData, SaveBook, DoBlaBlaBla)
 * @var $log_message string - Step Message (initially intended for adding a customized message to each iteration of a specific action ID, for example in loops, recursive functions, etc. Still not completely implemented
 * @var $parameters  mixed - Step Parameters (the values presented here will be - in debug mode - stored in a json-format in output for debugging/tracing intentions
 * @var $flags - Presentation Flags (Any combination of MLogEntry constants: SHOW_NONE, SHOW_TIME, SHOW_DURATION, SHOW_TOTAL_DURATION, SHOW_PARAMS, SILENT_END, SILENT_BEGIN, SHOW_MEMORY_USAGE, ECHO_SCREEN - the names might be self explanatory, will be writing about it more later)
 */
function begin($log_title, $log_message = null, $parameters = null, $flags = null)

// example:

$logger->begin("Main Entry Name", "Suffix - for more explanations", array('param1' => $param1, 'param2' => $param2, ...), MLogEntry::SILENT_BEGIN);
```

And ends with an ```finish()``` function:

```PHP
/**
 * @var $additional_parameters mixed - In case user needs to log some statistical data or anything when closing the log level.
 */
function finish($additional_parameters = null)
```

## Note 1:
As this is still in a PoC phase, there are some issues important issues to note:
1. finish()s are **really really** important, if you forget one, the level is not closed properly and all the upcoming logs are added as children.
2. using SILENT_BEGIN and SILENT_END together results in unexpected log ends and nesting problems, as they turn off logging beginning and ending messages in order to create 1-line informational logs, and if using both, then the line won't be present in the output..

## Note 2:
not sure if anyone is ever going to use this, but if someone used it and found problems or had any feedbacks or updates, I'd reallyx10 would like to hear it and have it in this code. So, please contact me :D Thanks

