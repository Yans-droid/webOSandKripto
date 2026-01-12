<?php

namespace App\Services;

class SchedulerService
{
    /**
     * @param array<int,array{pid:string,at:int,bt:int,prio?:int|null}> $processes
     */
    public function run(string $algo, array $processes, ?int $quantum = null, ?bool $preemptive = null): array
    {
        return match ($algo) {
        'fcfs'     => $this->fcfs($processes),
        'rr'       => $this->roundRobin($processes, $quantum ?? 2),
        'sjf'      => $this->sjf($processes),
        'srtf'     => $this->srtf($processes),
        'priority' => $this->priorityScheduling($processes, (bool)($preemptive ?? true)),
        default    => throw new \InvalidArgumentException("Unknown algorithm: $algo"),
    };
    }

    private function fcfs(array $processes): array
    {
        usort($processes, function ($a, $b) {
            return ($a['at'] <=> $b['at']) ?: strcmp($a['pid'], $b['pid']);
        });

        $t = 0;
        $timeline = [];
        $stats = [];
        $firstStart = [];

        foreach ($processes as $p) {
            $pid = $p['pid'];
            $at  = (int) $p['at'];
            $bt  = (int) $p['bt'];

            if ($t < $at) {
                $timeline[] = ['pid' => 'IDLE', 'start' => $t, 'end' => $at];
                $t = $at;
            }

            $start = $t;
            $end   = $t + $bt;

            $timeline[] = ['pid' => $pid, 'start' => $start, 'end' => $end];
            $firstStart[$pid] = $firstStart[$pid] ?? $start;

            $ct  = $end;
            $tat = $ct - $at;
            $wt  = $tat - $bt;
            $rt  = $firstStart[$pid] - $at;

            $stats[$pid] = ['ct' => $ct, 'tat' => $tat, 'wt' => $wt, 'rt' => $rt];
            $t = $end;
        }

        return $this->withAverages($timeline, $stats);
    }

    private function roundRobin(array $processes, int $q): array
    {
        usort($processes, fn ($a, $b) => ($a['at'] <=> $b['at']) ?: strcmp($a['pid'], $b['pid']));

        $n = count($processes);

        $rem = [];
        $at  = [];
        $bt  = [];
        foreach ($processes as $p) {
            $pid = $p['pid'];
            $at[$pid]  = (int) $p['at'];
            $bt[$pid]  = (int) $p['bt'];
            $rem[$pid] = (int) $p['bt'];
        }

        $t = 0;
        $i = 0;          // index proses yang sudah "arrive"
        $queue = [];
        $timeline = [];
        $stats = [];
        $firstStart = [];
        $doneCount = 0;

        // FIX UTAMA: $t harus by-reference (&$t)
        $pushArrivals = function () use (&$i, $n, $processes, &$queue, &$t) {
            while ($i < $n && (int) $processes[$i]['at'] <= $t) {
                $queue[] = $processes[$i]['pid'];
                $i++;
            }
        };

        $pushArrivals();

        while ($doneCount < $n) {
            if (empty($queue)) {
                // Anti-stuck: kalau tidak ada proses lagi yang akan datang
                if ($i >= $n) {
                    break;
                }

                $nextAt = (int) $processes[$i]['at'];
                if ($t < $nextAt) {
                    $timeline[] = ['pid' => 'IDLE', 'start' => $t, 'end' => $nextAt];
                    $t = $nextAt;
                }
                $pushArrivals();
                continue;
            }

            $pid = array_shift($queue);
            if ($rem[$pid] <= 0) {
                continue;
            }

            $start = $t;
            $firstStart[$pid] = $firstStart[$pid] ?? $start;

            $slice = min($q, $rem[$pid]);
            $t += $slice;
            $rem[$pid] -= $slice;

            // Merge segment kalau pid sama dengan segmen terakhir
            if (!empty($timeline)) {
                $lastIndex = count($timeline) - 1;
                if ($timeline[$lastIndex]['pid'] === $pid && $timeline[$lastIndex]['end'] === $start) {
                    $timeline[$lastIndex]['end'] = $t;
                } else {
                    $timeline[] = ['pid' => $pid, 'start' => $start, 'end' => $t];
                }
            } else {
                $timeline[] = ['pid' => $pid, 'start' => $start, 'end' => $t];
            }

            // Masukkan proses yang tiba selama time slice
            $pushArrivals();

            if ($rem[$pid] > 0) {
                $queue[] = $pid;
            } else {
                $ct  = $t;
                $tat = $ct - $at[$pid];
                $wt  = $tat - $bt[$pid];
                $rt  = $firstStart[$pid] - $at[$pid];

                $stats[$pid] = ['ct' => $ct, 'tat' => $tat, 'wt' => $wt, 'rt' => $rt];
                $doneCount++;
            }
        }

        return $this->withAverages($timeline, $stats);
    }
    private function sjf(array $processes): array
{
    // sort by arrival then pid
    usort($processes, fn($a,$b) => ($a['at'] <=> $b['at']) ?: strcmp($a['pid'],$b['pid']));

    $n = count($processes);
    $t = 0;
    $i = 0;
    $done = 0;

    $timeline = [];
    $stats = [];
    $firstStart = [];

    // store by pid
    $at = []; $bt = [];
    foreach ($processes as $p) {
        $at[$p['pid']] = (int)$p['at'];
        $bt[$p['pid']] = (int)$p['bt'];
    }

    $ready = []; // array of pids

    while ($done < $n) {
        // push arrivals
        while ($i < $n && (int)$processes[$i]['at'] <= $t) {
            $ready[] = $processes[$i]['pid'];
            $i++;
        }

        if (empty($ready)) {
            if ($i >= $n) break;
            $nextAt = (int)$processes[$i]['at'];
            $this->pushSeg($timeline, 'IDLE', $t, $nextAt);
            $t = $nextAt;
            continue;
        }

        // pick smallest burst time (tie: arrival then pid)
        usort($ready, function($pidA, $pidB) use ($bt, $at) {
            return ($bt[$pidA] <=> $bt[$pidB])
                ?: ($at[$pidA] <=> $at[$pidB])
                ?: strcmp($pidA, $pidB);
        });

        $pid = array_shift($ready);

        $start = $t;
        $firstStart[$pid] = $firstStart[$pid] ?? $start;

        $t += $bt[$pid];
        $this->pushSeg($timeline, $pid, $start, $t);

        $ct  = $t;
        $tat = $ct - $at[$pid];
        $wt  = $tat - $bt[$pid];
        $rt  = $firstStart[$pid] - $at[$pid];

        $stats[$pid] = ['ct'=>$ct,'tat'=>$tat,'wt'=>$wt,'rt'=>$rt];
        $done++;
    }

    return $this->withAverages($timeline, $stats);
}
private function srtf(array $processes): array
{
    usort($processes, fn($a,$b) => ($a['at'] <=> $b['at']) ?: strcmp($a['pid'],$b['pid']));
    $n = count($processes);

    $at=[]; $bt=[]; $rem=[];
    foreach ($processes as $p) {
        $pid = $p['pid'];
        $at[$pid] = (int)$p['at'];
        $bt[$pid] = (int)$p['bt'];
        $rem[$pid]= (int)$p['bt'];
    }

    $t = 0; $i = 0; $done = 0;
    $timeline=[]; $stats=[]; $firstStart=[];
    $ready=[];

    while ($done < $n) {
        $this->pushArrivalsUnique($processes, $i, $n, $t, $ready);
$ready = array_values(array_filter($ready, fn($pid) => ($rem[$pid] ?? 0) > 0));


        if (empty($ready)) {
            if ($i >= $n) break;
            $nextAt = (int)$processes[$i]['at'];
            $this->pushSeg($timeline, 'IDLE', $t, $nextAt);
            $t = $nextAt;
            continue;
        }

        // pick smallest remaining time
        usort($ready, function($pidA,$pidB) use ($rem,$at){
            return ($rem[$pidA] <=> $rem[$pidB])
                ?: ($at[$pidA] <=> $at[$pidB])
                ?: strcmp($pidA,$pidB);
        });

        $pid = $ready[0];

        $firstStart[$pid] = $firstStart[$pid] ?? $t;

        // run 1 unit
        $start = $t;
        $t++;
        $rem[$pid]--;

        $this->pushSeg($timeline, $pid, $start, $t);

        // arrivals during this 1 unit (at time t)
        $this->pushArrivalsUnique($processes, $i, $n, $t, $ready);
$ready = array_values(array_filter($ready, fn($pid) => ($rem[$pid] ?? 0) > 0));


        if ($rem[$pid] <= 0) {
            // remove pid from ready
            $ready = array_values(array_filter($ready, fn($x) => $x !== $pid));

            $ct  = $t;
            $tat = $ct - $at[$pid];
            $wt  = $tat - $bt[$pid];
            $rt  = $firstStart[$pid] - $at[$pid];

            $stats[$pid] = ['ct'=>$ct,'tat'=>$tat,'wt'=>$wt,'rt'=>$rt];
            $done++;
        }
    }

    return $this->withAverages($timeline, $stats);
}
private function priorityScheduling(array $processes, bool $isPreemptive = true): array
{
    usort($processes, fn($a,$b) => ($a['at'] <=> $b['at']) ?: strcmp($a['pid'],$b['pid']));
    $n = count($processes);

    $at=[]; $bt=[]; $prio=[]; $rem=[];
    foreach ($processes as $p) {
        $pid = $p['pid'];
        $at[$pid] = (int)$p['at'];
        $bt[$pid] = (int)$p['bt'];
        $prio[$pid] = isset($p['prio']) && $p['prio'] !== null ? (int)$p['prio'] : 999999;
        $rem[$pid]= (int)$p['bt'];
    }

    $t=0; $i=0; $done=0;
    $timeline=[]; $stats=[]; $firstStart=[];
    $ready=[];

    $pickBest = function() use (&$ready, $prio, $at, $rem) {
        usort($ready, function($a,$b) use ($prio,$at,$rem){
            return ($prio[$a] <=> $prio[$b])
                ?: ($rem[$a] <=> $rem[$b])        // tie-breaker: smaller remaining
                ?: ($at[$a] <=> $at[$b])
                ?: strcmp($a,$b);
        });
        return $ready[0] ?? null;
    };

    while ($done < $n) {
        $this->pushArrivalsUnique($processes, $i, $n, $t, $ready);
$ready = array_values(array_filter($ready, fn($pid) => ($rem[$pid] ?? 0) > 0));


        if (empty($ready)) {
            if ($i >= $n) break;
            $nextAt = (int)$processes[$i]['at'];
            $this->pushSeg($timeline, 'IDLE', $t, $nextAt);
            $t = $nextAt;
            continue;
        }

        $pid = $pickBest();
        if ($pid === null) break;

        $firstStart[$pid] = $firstStart[$pid] ?? $t;

        if ($isPreemptive) {
            // run 1 unit (preemptive)
            $start = $t;
            $t++;
            $rem[$pid]--;
            $this->pushSeg($timeline, $pid, $start, $t);

            $this->pushArrivalsUnique($processes, $i, $n, $t, $ready);
$ready = array_values(array_filter($ready, fn($pid) => ($rem[$pid] ?? 0) > 0));


            if ($rem[$pid] <= 0) {
                $ready = array_values(array_filter($ready, fn($x) => $x !== $pid));

                $ct  = $t;
                $tat = $ct - $at[$pid];
                $wt  = $tat - $bt[$pid];
                $rt  = $firstStart[$pid] - $at[$pid];

                $stats[$pid] = ['ct'=>$ct,'tat'=>$tat,'wt'=>$wt,'rt'=>$rt];
                $done++;
            }
        } else {
            // non-preemptive: run until completion
            $start = $t;
            $t += $rem[$pid];
            $rem[$pid] = 0;
            $this->pushSeg($timeline, $pid, $start, $t);

            $ready = array_values(array_filter($ready, fn($x) => $x !== $pid));

            // arrivals that came during execution (at time t)
            $this->pushArrivalsUnique($processes, $i, $n, $t, $ready);
$ready = array_values(array_filter($ready, fn($pid) => ($rem[$pid] ?? 0) > 0));

            $ct  = $t;
            $tat = $ct - $at[$pid];
            $wt  = $tat - $bt[$pid];
            $rt  = $firstStart[$pid] - $at[$pid];

            $stats[$pid] = ['ct'=>$ct,'tat'=>$tat,'wt'=>$wt,'rt'=>$rt];
            $done++;
        }
    }

    return $this->withAverages($timeline, $stats);
}

    private function pushSeg(array &$timeline, string $pid, int $start, int $end): void
{
    if ($end <= $start) return;

    $n = count($timeline);
    if ($n > 0) {
        $last = $n - 1;
        if ($timeline[$last]['pid'] === $pid && $timeline[$last]['end'] === $start) {
            $timeline[$last]['end'] = $end;
            return;
        }
    }
    $timeline[] = ['pid' => $pid, 'start' => $start, 'end' => $end];
}
private function pushArrivalsUnique(array $processes, int &$i, int $n, int $t, array &$ready): void
{
    while ($i < $n && (int)$processes[$i]['at'] <= $t) {
        $pid = $processes[$i]['pid'];
        if (!in_array($pid, $ready, true)) {
            $ready[] = $pid;
        }
        $i++;
    }
}




    private function withAverages(array $timeline, array $stats): array
    {
        $count = max(1, count($stats));
        $sumWT = 0; $sumTAT = 0; $sumRT = 0;

        foreach ($stats as $s) {
            $sumWT  += $s['wt'];
            $sumTAT += $s['tat'];
            $sumRT  += $s['rt'];
        }

        $averages = [
            'wt'  => $sumWT / $count,
            'tat' => $sumTAT / $count,
            'rt'  => $sumRT / $count,
        ];

        return compact('timeline', 'stats', 'averages');
    }
}
