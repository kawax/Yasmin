<?php
/**
 * Yasmin
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
 */

namespace CharlotteDunois\Yasmin\Utils;

use CharlotteDunois\Collect\Collection;
use CharlotteDunois\Events\EventEmitterInterface;
use Closure;
use OutOfBoundsException;
use RangeException;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;

/**
 * A collector is an util to collect elements from events.
 */
class Collector
{
    /**
     * @var EventEmitterInterface
     */
    protected $emitter;

    /**
     * @var string
     */
    protected $event;

    /**
     * @var callable
     */
    protected $handler;

    /**
     * @var callable|null
     */
    protected $filter;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Closure
     */
    protected $listener;

    /**
     * @var Closure
     */
    protected $resolve;

    /**
     * @var Closure
     */
    protected $reject;

    /**
     * @var Collection
     */
    protected $bucket;

    /**
     * @var \React\EventLoop\TimerInterface|TimerInterface
     */
    protected $timer;

    /**
     * @var LoopInterface
     */
    protected static $loop;

    /**
     * The handler gets invoked as `$handler(...$item)`. The optional filter get applied to look for a specific event (invoked as `$filter(...$item)`).
     *
     * Options may be:
     * ```
     * array(
     *     'max' => int, (maximum amount of elements to collect)
     *     'time' => int, (maximum amount of time (in seconds) to spend collecting (0 = unlimited), defaults to 30 seconds)
     *     'errors' => string[], (which unmet conditions lead to a promise rejection)
     * )
     * ```
     *
     * @param  EventEmitterInterface  $emitter
     * @param  string  $event
     * @param  callable  $handler  How the collect item should be handled. Must return an array of `[ $key, $value ]`.
     * @param  callable|null  $filter
     * @param  array  $options
     */
    function __construct($emitter, string $event, callable $handler, ?callable $filter = null, array $options = [])
    {
        $this->emitter = $emitter;
        $this->event = $event;
        $this->handler = $handler;
        $this->filter = $filter;
        $this->options = $options;
        $this->bucket = new Collection();
    }

    /**
     * Sets the Event Loop.
     *
     * @param  LoopInterface  $loop
     *
     * @return void
     * @internal
     */
    static function setLoop(LoopInterface $loop)
    {
        self::$loop = $loop;
    }

    /**
     * Starts collecting. Resolves with a collection.
     *
     * @return ExtendedPromiseInterface   This promise is cancellable.
     * @throws RangeException          The exception the promise gets rejected with, if collecting times out.
     * @throws OutOfBoundsException    The exception the promise gets rejected with, if the promise gets cancelled.
     */
    function collect()
    {
        return (new Promise(
            function (callable $resolve, callable $reject) {
                $this->resolve = $resolve;
                $this->reject = $reject;

                $filter = $this->filter;
                $handler = $this->handler;

                $this->listener = function (...$item) use (&$filter, &$handler) {
                    if ($filter === null || $filter(...$item)) {
                        [$key, $value] = $handler(...$item);
                        $this->bucket->set($key, $value);

                        if (($this->options['max'] ?? INF) <= $this->bucket->count()) {
                            $this->stop();
                        }
                    }
                };

                if (($this->options['time'] ?? 30) > 0) {
                    $this->timer = self::$loop->addTimer(
                        ($this->options['time'] ?? 30),
                        function () {
                            $this->stop();
                        }
                    );
                }

                $this->emitter->on($this->event, $this->listener);
            }, function (callable $resolve, callable $reject) {
            if ($this->timer) {
                self::$loop->cancelTimer($this->timer);
                $this->timer = null;
            }

            $this->emitter->removeListener($this->event, $this->listener);
            $reject(new OutOfBoundsException('Operation cancelled'));
        }
        ));
    }

    /**
     * This will stop the collector.
     *
     * @return void
     */
    function stop()
    {
        if ($this->timer) {
            self::$loop->cancelTimer($this->timer);
            $this->timer = null;
        }

        $this->emitter->removeListener($this->event, $this->listener);

        $errors = (array) ($this->options['errors'] ?? []);
        if (in_array('max', $errors, true) && ($this->options['max'] ?? 0) > 0 && $this->bucket->count(
            ) < $this->options['max']) {
            $reject = $this->reject;
            $reject(new RangeException('Collecting timed out (max not reached in time)'));

            return;
        }

        $resolve = $this->resolve;
        $resolve($this->bucket);
    }
}
