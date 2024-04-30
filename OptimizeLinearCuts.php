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
    public $cutLength             = [];
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

        foreach ($cutLengthIndexes as $partNo => $indexes) {

            // set the current part number
            $this->getNewBar($partNo);

            // reset the loop
            $partNoIsFinished = FALSE;
            $endOfCutLengths  = FALSE;
            $currentCutIndex  = 0; // start at the beginning of the list.

            while ($partNoIsFinished !== TRUE) {
                // cut the bar
                try {
                    $this->cutBar($this->cutLengths[$currentCutIndex], $endOfCutLengths);
                } catch (Exception $ex) {
                    switch($ex->getCode()){
                        case 1:
                            // ask for the next length
                            $currentCutIndex++;
                            break;
                        case 2: // we have run out of lengths to use
                            $partNoIsFinished = TRUE;
                            break;
                    }
                }
            }
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

    private function selectPartNo($partNo) {
        $this->currentPart = $partNo;
    }

    private function getNewBar($partNo) {
        $this->currentBarData = [
            "partNo"          => $partNo,
            "lengthRemaining" => self::STANDARD_LENGTH - ((self::END_CUTS + self::SAW_KERF) * 2)
        ];
    }

    private function cutBar($cutLengthData, $endOfCutLengths = FALSE) {

        $remainingLength = $this->currentBarData['lengthRemainging'] - $cutLengthData['length'];

        if ($remainingLength < 0 && $endOfCutLengths === FALSE) {
            // we ran out of space for this length, check for more.
            throw new Exception("Check for more cut lengths", 1);
        }
        
        if($endOfCutLengths){
            throw new Exception("End of needed cut lengths", 2);
        }

        $this->currentBarData = [
            "lengthRemaining" => $remainingLength,
        ];
    }
}

$optcuts = new OptimizeLinearCuts();
