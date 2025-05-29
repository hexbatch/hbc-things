# Hexbatch Things

Creates tree structures to run actions from the leaves up. The code for the actions and owners is made by the project which uses this.
When each action runs, optional hooks can be set before, after or for completion or error, these hooks can call http, code or fire events.

This is a laravel package that employes job queues and batches to run each task, and callback.


## Releases

# version 1.2.1 May 28
* Action wait timeout now set
* Thing response can be used as a code callback
* Fixup some issues with waiting and saving status
* Add migration to help with database resets; renamed cron job
* Simplified leaf gathering
* More settable properties from children trees
* Errors now have related tags
* Many bug fixes

# version 1.2.0  May 25, 2025
* Owner and action ids are now displayed as their uuid and not the numberic id.
  The interfaces and traits can now work with uuid and id.
* Validation of actions and owners can now work with uuid.
* Hook params can accept either a uuid or an id to set the action or owner filters.
* Things now have a wait timeout. The callbacks can set this. The thing will not be able to be resumed until after that time
* Hooks can set this wait timeout and status, if they are pre and blocking.
    * If using http, the body can set this having a key of wait_seconds,  
      or using header having the key hexbatch-wait-seconds will do it for http calls.
    * Events and functions return it via the interface.The ICallbackResponse now has an extra method to get the optional wait time.
    * Wait time that is non-null and 0 or less
      will be marked as the minimum wait time. (5 minutes)
* If callback status is set to fails, then if it is blocking before thing, the process chain will fail.
* Callbacks have a new property called is_halting_thing_stack, which if set to true will fail the process chain.
  If using this, the callback status can still be successful.
  This is set when a response wants to make the thing wait and its running before the thing.
* A new cron job runs once a minute to resume things that are waiting with the new wait timeout set
* Action and Owner types can now be up to 80 chars
* BUGFIX hooks cannot be set to only have an action name
* Moved releses to the readme



* # version 1.1.2  May 23, 2025
* Added status accessors on thing

* # version 1.1.1  May 18, 2025
* Decouple call response and hook handler in the interface

* # version 1.1.0  May 18, 2025
* Now can build new subtrees of the parent when a node finishes running

# version 1.0.0  May 16, 2025
* First working code

* # version 0.1.0  Feb 14, 2025
* First code
