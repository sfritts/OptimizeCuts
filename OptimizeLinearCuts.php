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
    const SAW_KERF        = 0.0625;
    const END_CUTS        = 1;

    public $barData               = [];
    public $cutLengths            = [];
    public $currentBarData        = [];
    public $currentPartNo         = [];
    public $currentCutLength      = 0;
    public $currentLengthQuantity = 0;

    public function __construct() {

        $this->setCutLengths();

        $cutLengthIndexes = [];
        $thisPart         = "";

        /** Get indexes * */
        foreach ($this->cutLengths as $index => $partData) {
            if ($thisPart !== $partData["partNo"]) {
                $thisPart = $partData['partNo'];
            }
            $cutLengthIndexes[$thisPart][] = $index;
        }

        foreach ($cutLengthIndexes as $partNo => &$cutIndexes) {
            
            // set the current part number
            $this->currentPartNo = $partNo;
            
            $this->getNewBar();

            // reset the loop
            $partNoIsFinished = FALSE;
            $endOfCutLengths  = FALSE;
            $currentCutIndex  = 0; // start at the beginning of the list.
            $countOfIndexes   = count($cutIndexes); // how many lengths are there?

            while ($partNoIsFinished !== TRUE) {
                // cut the bar
                try {
                    $this->cutBar($currentCutIndex, $endOfCutLengths);
                } catch (Exception $ex) {
                    var_dump($ex->getMessage());
                    switch ($ex->getCode()) {
                        case 1:
                            var_dump("-=-=-=-case 1-=-=-=-");
                            // check if we have anymore cut lengths
                            if ($currentCutIndex == ($countOfIndexes - 1)) {
                                // we ran out of cut lengths
                                $endOfCutLengths = TRUE;
                            } else {
                                // iterate to the next length
                                $currentCutIndex++;
                            }
                            break;
                        case 2: // we have run out of lengths to use
                            var_dump("-=-=-=-case 2-=-=-=-");
                            $partNoIsFinished = TRUE;
                            break;
                        case 3: // this length has no more cuts
                            var_dump("-=-=-=-case 3-=-=-=-");
                            unset($cutIndexes[$currentCutIndex]);
                            break;
                        case 4:
                        case 5:
                            $currentCutIndex = 0;
                            $endOfCutLengths = FALSE;
                            break;
                    }
                }
            }
            var_dump($this->currentBarData);
            die();
        }
    }

    /**
     * This should come from a database but for the purpose of this
     * example, and to make it portable, it is just an array.
     */
    public function setCutLengths() {
        $this->cutLengths = [
            ["partNo" => 500, "length" => 50, "quantity" => 12],
            ["partNo" => 495, "length" => 42, "quantity" => 12],
            ["partNo" => 495, "length" => 16, "quantity" => 12],
            ["partNo" => 500, "length" => 50, "quantity" => 4],
            ["partNo" => 500, "length" => 43, "quantity" => 4],
            ["partNo" => 123, "length" => 17, "quantity" => 19]
        ];

        array_multisort($this->cutLengths, SORT_DESC);
    }

    private function selectPartNo() {
        $this->currentPart = $this->currentPartNo;
    }

    private function getNewBar() {
        var_dump("-------------------------Get New Bar!!!");
        $this->currentBarData = [
            "partNo"          => $this->currentPartNo,
            "lengthRemaining" => self::STANDARD_LENGTH - ((self::END_CUTS + self::SAW_KERF) * 2),
            "cutsList"        => ""
        ];
    }

    private function cutBar($currentCutIndex, $endOfCutLengths = FALSE) {
        //var_dump($currentCutIndex);
        //var_dump($this->cutLengths[$currentCutIndex]['length']);

        $remainingLength = $this->currentBarData['lengthRemaining'] - $this->cutLengths[$currentCutIndex]['length'];
        var_dump("Current Cut Length: " . $this->cutLengths[$currentCutIndex]['length']);
        var_dump("Remainging Length " . $remainingLength);
        // does it fit on the bar?
        if ($remainingLength < $this->cutLengths[$currentCutIndex]['length']) {
            var_dump("Remaining is less than the cut length");
            if ($endOfCutLengths === FALSE) {
                var_dump("--Cut Lengths Remainging--");
                // we ran out of space for this length, check for more.
                throw new Exception("Check for more cut lengths", 1);
            } else {
                var_dump("--End of Cut Lengths--");
                // ran out of lengths to try. get a new bar and go back to the longest length
                $this->getNewBar();
                throw new Exception("Out of length, get new bar", 5);
            }
        }
        
        if($remainingLength < 0){
            // we need a new bar
            $this->getNewBar();
            throw new Exception("Got a new bar", 4);
        }

        // cut the bar and decrement the quantity by one.
        $this->currentBarData = [
            "lengthRemaining" => $remainingLength,
        ];
        
        // add one to current Length Quantity
        $this->currentLengthQuantity++;

        // remove one cut from this length
        $this->cutLengths[$currentCutIndex]['quantity']--;

        // are there anymore cuts at this length needed?
        if ($this->cutLengths[$currentCutIndex]['quantity'] == 0) {
            // remove this cut length from the list.
            unset($this->cutLengths[$currentCutIndex]);

            // save the cut description to the cuts string.
            $this->currentBarData["cutsList"] .= $this->currentLengthQuantity . " @ " . $this->cutLengths[$currentCutIndex]['length'];
            
            // notify the originating function so we can remove the index as well.
            throw new Exception("Remove Cut Length from List", 3);
        }

        if ($endOfCutLengths) {
            throw new Exception("End of needed cut lengths", 2);
        }
    }
}

$optcuts = new OptimizeLinearCuts();
