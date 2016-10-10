# multilevel-profiler
Hierarchical performance/ progress profiler/ logger

# Description
This PoC class allows developers and users to create a text-based tree-like log, which writes how much time and memory each section of code needs and it logs information in a hierarchical manner, so its users can see how much resources each subsction of their code needs.

The class can also be used as a production logging system. There are predefined modes, which allow users to select which information they want to have shown in their logs.

## Note
As this PoC began its life as a pure logger, I named it MLogger For Multi(L)evelLogger, might change its name sooner or later to [Something]Profiler as soon as I find a better name for it! :D

# Requirements
well, I can say it's working with PHP 5.3 (and I guess newer), cannot definitely tell if it works on older versions, have honestly not analysed it on older versions. 

# Installation
Just copy the file and include it!

# Configuration
Just use something like 

```
$logger = new MLogger(dirname(__FILE__).'/logs/', null, MLogger::LEVEL_DEBUG);
````

and you have an instance to use.

# Usage
