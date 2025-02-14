# Abstraction of things

it appears that the things are THE central logic point for this huge code. And I need to understand it and refine it before I put it in the mix.

As it is, too embedded to do anything with unless the rest of the code is brought up alongside it. This is too complex for me

So, it needs to be a data type by itself, outside of the project.

Description of this
* it has three types: api, actions, rules!!

api -> generates one or more actions
The actions do things, which generate zero or more events, the events spawn rules, these are actions too, and this must complete thruthfully if the action is to succeed
the api is only successful if its actions are successful

Where the abstraction comes in is that the api, actions and rules do not have to be defined as anything, except the rules generate a list of actions to do

so, the api fills the tree with a rule chain, the actions have subparts, states, which complete from the bottom up


Action:
has a parent action and zero or more children
the children are all done at the same time, or at least wait to be completed
has a return state based on its children, and internal data


Rule chain is a tree of actions

Api == rule chain, they are the same, so no need to use differnet terms

A source is a collection of action chains, an action when it runs can bring in more action chains to run before, or after and conditionally or independently

The tree is at the top, the last action to call, and all the leaves are where the concurrent actions are now running.
If a running action brings in another source of actions, then it can make it children to run before it.
Alternatively the new tree can run independenly either concurrently or after

As each action completes, that node and its children are removed from the thing.
When the source is reached (root of the tree), the thing will store its resule


The things have hooks that can run at different parts of the tree, and there are options for how different branches or the tree runs

So, an action has the functions/properties?
children (parent will point to the children)
function result: to decide what the result will be (run after the children finish), can also decide to pause, if paused will be asked again
function run: to optionally load in more actions as children. These run same time or after success or after fail

The result: will look at the children result: or use its own state or both

When an action is loaded into a thing, the run: will be called, and this adds to the thing tree

put A as a tree node (or root if no parents)
run A (it loads maybe more children or sibling to run at same time or on success or fail or always)
after A runs, go to each leaf and run each of those
after all children of a node is run, call the :result: function, the conditional branches can run then.
after all the conditional branches run, then the parent of the node can run, if all its other children are done


Not sure if the logic should be in the data structure or if its just that function

Here, the thing data, waits, is not used, that is a reference source for the main project to use
But the callbacks, results, hooks, options are used

settings used
thing_depth_limit
thing_rate_limit
thing_backoff_rate_policy
thing_pagination_size
thing_pagination_limit

hooks used
hook activated on action type
is_on
is_blocking
name
mode


The thing nodes do not point to data, but the running/waiting actions will point to the thing node.
this way, state can be kept per library that uses this

when ready to run a leaf, find the action via that other table. The thing can have the hint for which table as a short string.
That way, different data types can use the same thing tree
