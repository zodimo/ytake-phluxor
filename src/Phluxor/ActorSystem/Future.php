<?php

declare(strict_types=1);

namespace Phluxor\ActorSystem;

use Closure;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use Phluxor\ActorSystem;
use Phluxor\ActorSystem\Exception\FutureTimeoutException;
use Phluxor\Metrics\ActorMetrics;
use Phluxor\Metrics\PhluxorMetrics;
use Swoole\Coroutine\Channel;
use Swoole\Timer;

use function Swoole\Coroutine\go;

class Future
{
    use ActorSystem\Metrics\MetricsSystemTrait;

    /** @var Ref[] */
    private array $pipes = [];
    private mixed $result = null;
    private FutureTimeoutException|null $error = null;

    /** @var Closure[] */
    private array $completions = [];
    private bool $done = false;
    private Channel $channel;
    private int|false $timer = false;
    private FutureProcess $futureProcess;

    /**
     * @param ActorSystem $actorSystem
     * @param Ref|null $pid
     */
    private function __construct(
        private readonly ActorSystem $actorSystem,
        private Ref|null $pid = null,
    ) {
        $this->channel = new Channel(1);
    }

    /**
     * pid to the backing actor for the Future result.
     * @return Ref|null
     */
    public function pid(): Ref|null
    {
        return $this->pid;
    }

    public function setRef(Ref $pid): void
    {
        $this->pid = $pid;
    }

    public function pipeTo(Ref ...$pids): void
    {
        go(function (Ref ...$pids) {
            $this->channel->push(true);
            $this->pipes = array_merge($this->pipes, $pids);
            if ($this->done) {
                $this->sendToPipes();
            }
            $this->channel->pop();
        }, ...$pids);
    }

    private function sendToPipes(): void
    {
        if (!count($this->pipes)) {
            return;
        }
        $message = $this->error ?? $this->result;
        foreach ($this->pipes as $pid) {
            $pid->sendUserMessage($this->actorSystem, $message);
        }
        $this->pipes = [];
    }

    public function result(): FutureResult
    {
        $err = $this->wait();
        return new FutureResult($this->result, $err);
    }

    /**
     * @return Channel
     */
    public function getChannel(): Channel
    {
        return $this->channel;
    }

    /**
     * @param mixed $result
     * @return void
     */
    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }

    /**
     * @param FutureTimeoutException $error
     * @return void
     */
    public function setError(FutureTimeoutException $error): void
    {
        $this->error = $error;
    }

    /**
     * @param Closure(FutureResult): void $continuation
     * @return void
     */
    public function continueWith(Closure $continuation): void
    {
        $this->channel->push(true);
        if ($this->done) {
            $continuation(new FutureResult($this->result, $this->error));
        } else {
            $this->completions[] = $continuation;
        }
        $this->channel->pop();
    }

    private function runCompletion(): void
    {
        foreach ($this->completions as $continuation) {
            $continuation(new FutureResult($this->result, $this->error));
        }
        $this->completions = [];
    }

    public function stop(Ref $pid): void
    {
        // already stopped
        if ($this->done) {
            // send to pipes
            if (!$this->channel->isEmpty()) {
                // channel is full
                $this->channel->pop();
            }
            return;
        }
        $this->done = true;
        if ($this->timer !== false) {
            Timer::clear($this->timer);
        }
        $this->actorSystem->getProcessRegistry()->remove($pid);
        $this->sendToPipes();
        $this->runCompletion();
        if (!$this->channel->isFull()) {
            // send to pipes
            $this->channel->push(true);
        }
        if ($this->timer === false) {
            // clear channel
            $this->channel->pop();
            return;
        }
        if (!$this->channel->isEmpty()) {
            $this->channel->pop();
        }
    }

    /**
     * @return FutureTimeoutException|null
     */
    public function wait(): FutureTimeoutException|null
    {
        if ($this->done) {
            return $this->error;
        }
        // チャネルからのメッセージを待つ
        $result = $this->channel->pop();
        if ($result === true) {
            // タイムアウトまたはエラー
            return $this->error;
        }
        return null;
    }

    /**
     * @param ActorSystem $actorSystem
     * @param int $duration seconds
     * @return Future
     */
    public static function create(ActorSystem $actorSystem, int $duration): Future
    {
        $futureProcess = new FutureProcess(new Future($actorSystem, null));
        $registry = $actorSystem->getProcessRegistry();
        $f = $futureProcess->getFuture();
        $f->futureProcess = $futureProcess;
        $r = $registry->add($futureProcess, sprintf("future%s", $registry->nextId()));
        $pid = $r->getRef();
        if (!$r->isAdded()) {
            $actorSystem->getLogger()->error("Failed to register future process", ['pid' => $pid]);
        } else {
            $metricsSystem = $f->enabledMetricsSystem($actorSystem);
            if ($metricsSystem) {
                $instruments = $metricsSystem->metrics()->find(PhluxorMetrics::INTERNAL_ACTOR_METRICS);
                if ($instruments instanceof ActorMetrics) {
                    $instruments->getFuturesStartedCount()
                        ->add(
                            1,
                            Attributes::create([
                                'address' => $actorSystem->address(),
                                'actor_ref' => (string)$pid,
                            ])
                        );
                }
            }
            $f->setRef($pid);
        }
        if ($duration >= 0) {
            $f->timer = \Swoole\Timer::after($duration * 1000, function () use ($f, $pid) {
                if (!$f->done) {
                    $f->setError(new ActorSystem\Exception\FutureTimeoutException("future: timeout"));
                    $f->channel->push(true);
                }
                $f->stop($pid);
            });
        }
        return $f;
    }

    /**
     * @return Ref[]
     */
    public function pipes(): array
    {
        return $this->pipes;
    }

    /**
     * @return ActorSystem
     */
    public function getActorSystem(): ActorSystem
    {
        return $this->actorSystem;
    }

    public function isError(): bool
    {
        return $this->error !== null;
    }
}
