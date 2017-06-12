<?php

class Substance
{
	protected $flagFor = false; // 
	protected $firstIndex = 1;
    protected $brackets = [];
    protected $elements = [
        'arrOfElements' => [],
        'elements' => []
    ];
    public $id = '';
    public $formula = '';


    public function __construct($str)
    {
        $this->formula = $str;
    }

    public function getId()
    {
        $arr = [];
        $str = preg_replace('/\s/', '', $this->formula);

        $this->findBrackets($str);
        $this->findIndexes(true);

		
		if( !is_array($this->elements['array']) ){
			throw new Exception($this->formula);
		}
        foreach ($this->elements['array'] as $k => $v) {
            $index = ($this->elements['array'][$k][0] > 1) ? $this->elements['array'][$k][0] : '';
            $arr[] = $k . $index;
        }

        sort($arr);
        $this->id = implode('', $arr);
        return $this->id;
    }

    public function findBrackets($x = false)
    {
        $sub = $x ? $x : $this->formula;
        $sub = preg_replace('/\(([A-Z][a-z]?)\)/', '$1', $sub);
        $sub = str_replace('()', '', $sub);

        preg_match_all('%([A-Z][a-z]?|\d{1,4}|\[|\]|\(|\)|\*|\{|\}|e|-)%', $sub, $this->elements['arrOfElements']);
        $this->elements['arrOfElements'] = $this->elements['arrOfElements'][0];

        if (preg_match('/(\(|\)|\[|\]|\*|\{|\})/', $sub) == 0) {
            $this->brackets = [
                'anchor' => false,
                'charge' => 0
            ];
            return;
        }
        $this->brackets = [
            'br' => ['(', ')', '[', ']', '*'],
            'dept' => [0 => 0, 2 => 0],
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            'anchor' => true,
            'charge' => 0
        ];

        foreach ($this->elements['arrOfElements'] as $k => $v) {
            switch ($v) {
                case '(':
                case '[':
                    $x = array_search($v, $this->brackets['br']);
                    $this->brackets['dept'][$x] += 1;

                    if (!isset($this->brackets[$x][$this->brackets['dept'][$x] - 1])) {
                        $this->brackets[$x][$this->brackets['dept'][$x] - 1] = $k;
                    } elseif (!isset($this->brackets[$x][$this->brackets['dept'][$x] - 1 + 10])) {
                        $this->brackets[$x][$this->brackets['dept'][$x] - 1 + 10] = $k;
                    } else {
                        $this->brackets[$x][] = $k;
                    }
                    break;
                case ')':
                case ']':
                    $x = array_search($v, $this->brackets['br']);
                    $this->brackets['dept'][$x - 1] -= 1;
                    if (!isset($this->brackets[$x][$this->brackets['dept'][$x - 1]])) {
                        $this->brackets[$x][$this->brackets['dept'][$x - 1]] = $k;
                    } elseif (!isset($this->brackets[$x][$this->brackets['dept'][$x - 1] + 10])) {
                        $this->brackets[$x][$this->brackets['dept'][$x - 1] + 10] = $k;
                    } else {
                        $this->brackets[$x][] = $k;
                    }
                    break;
                case '*':
                    $x = array_search($v, $this->brackets['br']);
                    $this->brackets[$x][] = $k;
            }
        }


        if ($this->brackets['dept'][0] > 0 || $this->brackets['dept'][1] > 0) {
            throw new Exception('Недостаточно закрывающих скобок ' . $this->formula);
        }
        if ($this->brackets['dept'][0] < 0 || $this->brackets['dept'][1] < 0) {
            throw new Exception('Недостаточно открывающих скобок' . $this->formula);
        }

        unset($this->brackets['dept']);

        if (strpos($sub, '{') !== false) {
            $charge = substr($sub, strpos($sub, '{') + 1);
            if (strpos($charge, '-') !== false && (int)$charge > 0) {
                $this->brackets['charge'] = (int)$charge * -1;
            } else {
                $this->brackets['charge'] = (int)$charge;
            }
        }
    }

    public function findIndexes($b = false)
    {
        foreach ($this->elements['arrOfElements'] as $j => $item) {

            $indexOfElement = 1;

            if (is_numeric($this->elements['arrOfElements'][0])) {
                $this->firstIndex = $indexOfElement *= $this->elements['arrOfElements'][0];
            }

            if (preg_match('/(\d{1,8}|\(|\)|\[|\]|\*|-|\{|\})/', $item)) {
                continue;
            }

            $indexOfElement = $this->parceIndexes($j, $indexOfElement);

            if ($b) {
                if (array_search($item, $this->elements['elements']) === false) {
                    $this->elements['elements'][] = $item;
                    $this->elements['array'][$item] = [0, 0];
                }

                $this->elements['array'][$item][0] += $indexOfElement;
            } else {
                $this->elements['array'][$item][1] += $indexOfElement;
            }
        }
    }

    protected function parceIndexes($j, $indexOfElement)
    {
        $indexOfElement *= (isset($this->elements['arrOfElements'][$j + 1]) && is_numeric($this->elements['arrOfElements'][$j + 1])) ? $this->elements['arrOfElements'][$j + 1] : 1;
        if ($this->brackets['anchor']) {
            // если были скобки то пересчитываем индекс
            for ($k = 0, $bracketsLength = count($this->brackets['br']); $k < $bracketsLength; $k += 2) {
                foreach ($this->brackets[$k] as $l => $v) {

                    if ($k < $bracketsLength - 1 && $j > $v && $j < $this->brackets[$k + 1][$l]) {
                        $x = isset($this->brackets[$k + 1][$l]) && is_numeric($this->elements['arrOfElements'][$this->brackets[$k + 1][$l] + 1]) && $this->elements['arrOfElements'][$this->brackets[$k + 1][$l] + 1] > 0 ? $this->elements['arrOfElements'][$this->brackets[$k + 1][$l] + 1] : 1; // индекс после скобки
                        $indexOfElement *= $x;
                    } elseif ($this->brackets['br'][$k] == "*" && $j > $v) {
                        if (count($this->brackets[$k]) == 1) {
                            $y = is_numeric($this->elements['arrOfElements'][$v + 1]) && $this->elements['arrOfElements'][$v + 1] > 0 ? $this->elements['arrOfElements'][$v + 1] : 1; // индекс после *
                            $indexOfElement *= $y; // $this->firstIndex; // /$firstIndex;  для другого поведения 2Na2SO4*5H2O
                        } else {
                            if ($j < $this->brackets[$k][$l + 1]) {
                                $z = is_numeric($this->elements['arrOfElements'][$v + 1]) && $this->elements['arrOfElements'][$v + 1] > 0 ? $this->elements['arrOfElements'][$v + 1] : 1; // индекс после *
                                $indexOfElement *= $z; // $this->firstIndex; // /$firstIndex;  для другого поведения 2Na2SO4*5H2O
                            } else {
                                $q = isset($this->brackets[$k][$l + 1]) && is_numeric($this->elements['arrOfElements'][$this->brackets[$k][$l + 1] + 1]) && $this->elements['arrOfElements'][$this->brackets[$k][$l + 1] + 1] > 0 ? $this->elements['arrOfElements'][$this->brackets[$k][$l + 1] + 1] : 1; // индекс после *
                                $indexOfElement *= $q; // $this->firstIndex; // /$firstIndex;  для другого поведения 2Na2SO4*5H2O
                            }
                        }
                    }
                }
            }
            return $indexOfElement;
        }
        return $indexOfElement;
    }

}
