<?php

/**
 * Description of OptimizeLinearCuts
 * 
 * Create report showing how to cut an assorted list of cut lengths and their
 * quantities with minimal waste.
 *
 * @author Stewart Fritts <stewart.fritts@gmail.com>
 */
class OptimizeLinearCuts {

    /**
     * Bar Length in inches.
     */
    const STANDARD_LENGTH = 234;

    /**
     * Account for the width of the saw blade.
     */
    const SAW_KERF = 0.0625;

    /**
     * Length to cut off ends to square up material.
     */
    const END_CUTS = 1;

    /**
     * Store the finished bar information here.
     * 
     * @var array barData
     */
    public $barData = [];

    /**
     * Container for cut lengths to be processed.
     * 
     * @var array cutLengths
     */
    public $cutLengths = [];

    /**
     * Holds indexes from this->cutLengths, grouping them by partNo for easier
     *     navigation
     * 
     * @var array cutLengthIndexes
     */
    public $cutLengthIndexes      = [];
    public $currentBarData        = [];
    public $currentPartNo         = [];
    public $currentCutLength      = 0;
    public $currentLengthQuantity = 0;

    public function __construct() {

        $this->setCutLengths();

        $this->setCutLengthIndexes();

        foreach ($this->cutLengthIndexes as $partNo => $indexList) {

            // set the current part number
            $this->currentPartNo = $partNo;

            $this->getNewBar(TRUE); // get first bar for this part number.
            // reset the loop
            $nextPartNumber  = FALSE;
            $endOfCutLengths = FALSE;
            $currentCutIndex = 0; // start at the beginning of the list.
            $countOfIndexes  = count($indexList); // how many length indexes are there to start with?

            while ($nextPartNumber === FALSE) {
                // cut the bar
                // responses : 
                //     cut was successful. got to the next one.
                //     cut failed:
                //         IF the bar is not long enough but there are cuts smaller lengths left to try
                //             move to the next length
                //         IF the bar is not long enough and there are not any smaller lengths left to try
                //             check if there are anymore cut lengths left.
                //                 IF there are lengths left, go to the longest one and get a new bar.
                //                 IF no cuts remaining. got to the next part number.
                //
                if ($this->cutBar($currentCutIndex, $endOfCutLengths)) {
                    $this->removeCutLengthQuantity($currentCutIndex);

                    echo("CutLength Quantity: Length " . $this->cutLengths[$currentCutIndex]["length"] . " :: Quantity " . $this->cutLengths[$currentCutIndex]["quantity"] . "\n");

                    $lengthsRemaining = $this->checkForRemainingLengthOptions($indexList);
                    if ($lengthsRemaining !== FALSE) {
                        $currentCutIndex = $lengthsRemaining;
                    } else {
                        $nextPartNumber = TRUE;
                    }
                } else {
                    echo "\nCut doesn't fit. \n";
                    // are there other length options?
                    if ($countOfIndexes > 1) {
                        echo "Other Quantities Available.\n";
                        $lookForNextQuantity = TRUE;
                        while ($lookForNextQuantity === TRUE) {
                            $currentCutIndex++; // move to the next cut length option.
                            echo "Looking for index: " . $currentCutIndex . "\n";
                            if (in_array($currentCutIndex, $indexList)) {
                                echo "Found index: " . $currentCutIndex . "\n";
                                if ($this->checkCutLengthRemaining($currentCutIndex)) {
                                    $lookForNextQuantity = FALSE; // stop looking
                                }
                            } else {
                                echo "Index: " . $currentCutIndex . " was not found.";
                                $lengthsRemaining = $this->checkForRemainingLengthOptions($indexList);
                                if ($lengthsRemaining !== FALSE) {
                                    $currentCutIndex = $lengthsRemaining;
                                    $this->getNewBar();
                                } else {
                                    // we ran out of length options
                                    $nextPartNumber = TRUE;
                                }
                                $lookForNextQuantity = FALSE; // stop looking
                            }
                        }
                    } else {
                        if ($this->checkCutLengthRemaining($currentCutIndex)) {
                            $this->getNewBar();
                        } else {
                            $nextPartNumber = TRUE;
                        }
                    }
                }
            }
            var_dump($this->currentBarData);
        }
    }

    private function checkCutLengthRemaining($cutIndex) {
        if ($this->cutLengths[$cutIndex]["quantity"] > 0) {
            return TRUE;
        }
        return FALSE;
    }

    private function checkForRemainingLengthOptions($indexList) {
        $lengthsRemaining = FALSE;
        foreach ($indexList as $index) {
            if ($this->cutLengths[$index]["quantity"] > 0) {
                return $index;
            }
        }
        return FALSE;
    }

    private function setCutLengthIndexes() {

        $thisPart = "";

        /** Get indexes * */
        foreach ($this->cutLengths as $index => $partData) {
            if ($thisPart !== $partData["partNo"]) {
                $thisPart = $partData['partNo'];
            }
            $this->cutLengthIndexes[$thisPart][] = $index;
        }
    }

    /**
     * This should come from a database but for the purpose of this
     * example, and to make it portable, it is just an array.
     */
    private function setCutLengths() {
        /**
          $this->cutLengths = [
          ["partNo" => 500, "length" => 50, "quantity" => 12],
          ["partNo" => 495, "length" => 42, "quantity" => 12],
          ["partNo" => 495, "length" => 16, "quantity" => 12],
          ["partNo" => 500, "length" => 50, "quantity" => 4],
          ["partNo" => 500, "length" => 43, "quantity" => 4],
          ["partNo" => 123, "length" => 17, "quantity" => 19]
          ];* */
        $this->cutLengths = [
            ["partNo" => 100, "length" => 50, "quantity" => 12],
            ["partNo" => 100, "length" => 45, "quantity" => 5],
            ["partNo" => 100, "length" => 12, "quantity" => 9],
        ];

        array_multisort($this->cutLengths, SORT_DESC);
    }

    private function getNewBar($firstBar = FALSE) {
        echo("\nGet New Bar!!!\n");

        // update bar info...after first bar select.

        $this->currentBarData = [
            "partNo"          => $this->currentPartNo,
            "lengthRemaining" => self::STANDARD_LENGTH - ((self::END_CUTS + self::SAW_KERF) * 2),
            "cutsList"        => ""
        ];

        $this->currentLengthQuantity = 0;
    }

    private function updateBarData() {
        $this->barData[] = [
            "barQuantity"      => 1,
            "stringDecription" => $this->currentBarData["cutsList"]
        ];
    }

    private function removeCutLengthQuantity($currentCutIndex) {
        $this->cutLengths[$currentCutIndex]["quantity"]--;
    }

    private function removeCutLength($currentCutIndex) {
        $this->cutLengths[$currentCutIndex]["lengthRemaining"];
    }

    private function cutBar($currentCutIndex, $endOfCutLengths = FALSE) {
        echo "\ncutBar: Attempting to cut bar. Length: " .($this->cutLengths[$currentCutIndex]['length'] + self::SAW_KERF). "\n";
        $newRemainingLength = $this->currentBarData['lengthRemaining'] - ($this->cutLengths[$currentCutIndex]['length'] + self::SAW_KERF);

        // does the cut fit on this bar?
        echo "New Remaining Length: Part Number: " . $this->currentBarData['partNo'] . " @ " . $this->currentBarData['lengthRemaining'] . "\n";
        if ($newRemainingLength > 0) {
            $this->currentBarData['lengthRemaining'] = $newRemainingLength;
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

$optcuts = new OptimizeLinearCuts();
