<?php

/**
 * This file contains the \QUI\Utils\Tracking\Timer
 */

namespace QUI\Utils\Tracking;

use function explode;
use function microtime;
use function round;

/**
 * A timer
 * measures the length of time between measurement points
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class Timer
{
    /**
     * All time milstones
     *
     * @var array
     */
    protected array $milestones;

    /**
     * Time calc
     *
     * @return float
     */
    protected function time(): float
    {
        [$uTime, $time] = explode(" ", microtime());

        return ((float)$uTime + (float)$time);
    }

    /**
     * Set measurement point
     *
     * @param string $name - name of the point
     */
    public function milestone(string $name)
    {
        $this->milestones[] = [$name, $this->time()];
    }

    /**
     * Returns the time measurement result as an array
     *
     * @return array
     */
    public function result(): array
    {
        $this->milestone('finish');

        return $this->milestones;
    }

    /**
     * Returns the time measurement results as HTML
     *
     * @return string
     */
    public function resultStr(): string
    {
        $result = $this->result();

        $output = '<table>' . "\n" .
            '<tr>' .
            '<th>Messpunkt</th>' .
            '<th>Diff</th>' .
            '<th>Cumulative</th>' .
            '</tr>' . "\n";

        foreach ($result as $key => $data) {
            $output .= '<tr><td>' . $data[0] . '</td>' .
                '<td>' . round(($key ? $data[1] - $result[$key - 1][1] : '0'), 5)
                . '</td>' .
                '<td>' . round(($data[1] - $result[0][1]), 5) . '</td></tr>' . "\n";
        }

        $output .= '</table>';

        return $output;
    }

    /**
     * Returns the time measurement result for the bash / console
     *
     * @return array
     */
    public function resultConsole(): array
    {
        $result = $this->result();

        foreach ($result as $key => $data) {
            $data[2] = round(
                ($key ? $data[1] - $result[$key - 1][1] : '0'),
                5
            ); // Diff
            $data[3] = round(($data[1] - $result[0][1]), 5); // Cumulative

            $result[$key] = $data;
        }

        return $result;
    }
}
