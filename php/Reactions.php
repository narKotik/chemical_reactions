<?php

include __DIR__ . '/Substance.php';

Class Reaction
    extends Substance
{
    const GAUSS_ACCURACY = 0.0000001;
    const ACCURACY = 0.0000001;
    const GAUSS_OK = 0;
    const GAUSS_NOSOL = 1;
    const GAUSS_MANYSOL = 2;
    const ERROR_NOT_FOUND_EQUAL_SIGN = 'Нет знака равно "="';
    const ERROR_DOUBLE_EQUAL_SIGN = 'Два знака равно "="';
    const ERROR_DOUBLE_STAR_SIGN = 'Два знака равно "="';
    const NOT_MATCH = ' != ';
    const ELEMENTS_IS_BEFORE_BUT_AFTER_NOT = 'Елементы есть до \'=\' но нет после: ';
    const ELEMENTS_IS_AFTER_BUT_BEFORE_NOT = 'Елементы есть после \'=\' но нет до: ';
    const TO_MANY_SUBSTANCES = 'Слишком много соединений.';
    const ERROR_DOUBLE_SUBSTANCES = 'Вещество повторилось до и после равно: ';


    protected $brackets = [];
    protected $elements = [
        'arrOfElements' => [],
        'elements' => []
    ];


    public $strbefore = '';
    public $strafter = '';
    public $arrbeforeRaw = [];
    public $arrbefore = [];
    public $arrafterRaw = [];
    public $arrafter = [];
    public $reaction = '';
    public $reactionClear = '';
    const FIRST_T = "webscraber";
    const SECOND_T = "webscraber2";
    public $firstOrSecond = self::FIRST_T;
    public $regExpElements = '/(A[cglmrstu]|B[aehikr]?|C[adeflmnorsu]?|D[bsy]?|E[rsu]|F[emflr]?|G[ade]|H[efgos]?|I[nr]?|Kr?|L[airvu]|M[cdgnot]|N[abdehiop]?|O[sg]?|P[abdmortu]?|R[abefghnu]|S[bcegimnr]?|T[abcehilms]?|U|V|W|Xe|Yb?|Z[nr])/';
    public $regExpElementsVithBrackets = '/(A[cglmrstu]|B[aehikr]?|C[adeflmnorsu]?|D[bsy]?|E[rsu]|F[emflr]?|G[ade]|H[efgos]?|I[nr]?|Kr?|L[airvu]|M[cdgnot]|N[abdehiop]?|O[sg]?|P[abdmortu]?|R[abefghnu]|S[bcegimnr]?|T[abcehilms]?|U|V|W|Xe|Yb?|Z[nr]|\d{1,4}|\[|\]|\(|\)|\*|\{|\}|e|-|\+|\=)/';


    public function __construct($str)
    {
        $this->reaction = $str;
        $this->reactionClear = $str = $this->clearFromTrash($str);

        list($this->strbefore, $this->strafter) = preg_split('/(=|→)/', $str);

        $this->arrbeforeRaw = explode('+', $this->strbefore);
        $this->arrafterRaw = explode('+', $this->strafter);

        foreach ($this->arrbeforeRaw as $k => $item) {
            if ($pos = (int)$item) {
                $this->arrbefore[$k] = substr($item, strlen((string)$pos));
            } else {
                $this->arrbefore[$k] = $item;
            }
        }

        foreach ($this->arrafterRaw as $k => $item) {

            if ($pos2 = (int)$item) {
                $this->arrafter[$k] = substr($item, strlen((string)$pos2));
            } else {
                $this->arrafter[$k] = $item;
            }
        }
    }

    public function getStrB()
    {
        sort($this->arrbefore);
        return '|' . implode('|', $this->arrbefore) . '|';
    }

    public function getStrA()
    {
        sort($this->arrafter);
        return '|' . implode('|', $this->arrafter) . '|';
    }

    public function clearFromTrash($str)
    {
        $str = preg_replace('/[\(\[\{][\)\]\}]/', '', $str);
        $str = preg_replace('/\s\((примесь|in|\$.|T =|pH =|P = ).*\)/', '', $str);
        $str = preg_replace('/([A-Z][a-z]?)0+/', '$1', $str);
        $str = preg_replace('/\s\(([a-z]|[а-яА-Я]|[0-9])(.*?)\)|[↑↓]|\$[a-z]{1,2}/', '', $str);
        $str = preg_replace('/\s/', '', $str);
        $str = preg_replace('/\{\+?(\d{1,2})\+?\}/', '{$1}', $str);
        $str = str_replace(['–', '−'], '-', $str);
        $str = str_replace('{-}', '{-1}', $str);
        $str = str_replace('{+}', '{1}', $str);
        $str = str_replace('\u2192', '=', $str);

        return trim(str_replace('  ', ' ', $str));
    }

    public function clearFromCoefficient()
    {
        return implode(' + ', $this->arrbefore) . ' = ' . implode(' + ', $this->arrafter);
    }

    protected function varietyOfElements()
    {
        $answer = [];
        $arrB = [];
        $arrA = [];
        preg_match_all($this->regExpElements, $this->strbefore, $arrB);
        preg_match_all($this->regExpElements, $this->strafter, $arrA);
        $b = [];
        foreach (array_unique($arrB[0]) as $v) {
            $b[] = $v;
        }
        $a = [];
        foreach (array_unique($arrA[0]) as $v) {
            $a[] = $v;
        }
        $difference1 = array_diff($a, $b); // значение $difference1 есть после но нет до
        $difference2 = array_diff($b, $a); // значение $difference2 есть до но нет после

        $answer['elements'] = $b;
        if (count($difference1)) {
            $answer['errorA'] = $difference1;
        }
        if (count($difference2)) {
            $answer['errorB'] = $difference2;
        }

        return $answer;
    }

    public function findIndexesS(&$arrayMatrix, &$reaction, $i)
    {
        foreach ($this->elements['arrOfElements'] as $j => $item) {
            $indexOfElement = 1;

            if (is_numeric($this->elements['arrOfElements'][0])){
                $firsIndex = $indexOfElement *= $this->elements['arrOfElements'][0];
            }

            if (preg_match('/(\d{1,8}|\(|\)|\[|\]|\*|-|\{|\})/', $item)) {
                continue;
            }

            $indexOfElement = $this->parceIndexes($j, $indexOfElement);

            $indexCol = array_search($item, $reaction['elements']);
            if (!isset($arrayMatrix[$indexCol][$i])) {
                $arrayMatrix[$indexCol][$i] = 0;
            }
            $arrayMatrix[$indexCol][$i] += $indexOfElement;
        }
    }

    protected function gauss(&$a, $n, $u, &$x)
    {
        /*** Проверка u и n ***/
        if ($n > $u) return self::GAUSS_MANYSOL;
        /*** Приведение к диагональному виду ***/
        for ($j = 0; $j < $n; $j++) {
            /* а) поиск строки с наибольшим по модулю элементом */
            $d = abs($a[$j][$j]);
            $sn = $j;
            for ($i = $j; $i < $u; $i++) {
                if (abs($a[$i][$j]) > $d) {
                    $d = abs($a[$i][$j]);
                    $sn = $i;
                }
            }
            /* б) перенос строки на надлежащее место */
            for ($k = 0; $k <= $n; $k++) {
                $d = $a[$sn][$k];
                $a[$sn][$k] = $a[$j][$k];
                $a[$j][$k] = $d;
            }

            /* в) деление ведущего ур-я на главный элемент */
            $d = $a[$j][$j];

            if ($d)
                for ($k = 0; $k <= $n; $k++) {
                    $a[$j][$k] = $a[$j][$k] / $d;
                }
            else
                for ($k = 0; $k <= $n; $k++) {
                    $a[$j][$k] = 0;
                }

            /* г) вычитание данной строки из всех остальных */
            /*    с домножением на коэффициент */
            for ($i = 0; $i < $u; $i++) {
                if ($i == $j) continue;  /* Не трогаем вычит. строку */
                $d = -$a[$i][$j];
                for ($k = 0; $k <= $n; $k++) { /* Вычитание */
                    $a[$i][$k] = $a[$i][$k] + $a[$j][$k] * $d;
                }
            }
        }
        /*** Вычисление корней ***/
        /* а) проверка системы на разрешимость */
        if ($u > $n) {
            //i от 3 до 5 должно быть
            for ($i = $n; $i < $u; $i++) {
                $k = 0;
                // j  от 0 до 3
                for ($j = 0; $j < $n; $j++)
                    if (abs($a[$i][$j]) > self::GAUSS_ACCURACY) $k = 1;
                if ($k == 0 && abs($a[$i][$j]) > self::GAUSS_ACCURACY) return 1; //Math.abs(a[i][n])
            }
        }

        /* б) поиск корней */
        for ($i = 0; $i < $n; $i++) {
            $x[$i] = -$a[$i][$n];
            if ($a[$i][$i] != 1) {
                /** Обработка ошибок **/
                if ($x[$i])
                    return self::GAUSS_NOSOL; /* Решений нет */
                else
                    return self::GAUSS_MANYSOL; /* Бесконечно много решений */
            }
            if (abs($x[$i]) < self::GAUSS_ACCURACY) $x[$i] = 0; /* Обнуление слишком малых знач. */
        }
        return self::GAUSS_OK; /* Нормальное завершение работы */
    }

    protected function cmpzero($x)
    {
        return (abs($x) > self::ACCURACY);
    }

    public function calculateReactionsCoef($str = false)
    {
        $str = $str ?: $this->reactionClear;
        $chargedSubstance = 0;
        $arrayMatrix = [];
        $arrayX = [];

        if (strpos($str, '=') === false) throw new Exception(self::ERROR_NOT_FOUND_EQUAL_SIGN);
        if (strpos($str, '=') != strrpos($str, '=')) throw new Exception(self::ERROR_DOUBLE_EQUAL_SIGN);
        if (strpos($str, '**') !== false) throw new Exception(self::ERROR_DOUBLE_STAR_SIGN);


        $doubbleSub = array_intersect($this->arrbefore, $this->arrafter);
        if ( count($doubbleSub) > 0 ) {
            throw new Exception(self::ERROR_DOUBLE_SUBSTANCES . implode(',', $doubbleSub));
        }

        $reaction = $this->varietyOfElements();

        $reaction['substances'] = array_merge($this->arrbefore, $this->arrafter);

        // проверка синтаксиса
        if (isset($reaction['errorA']) && isset($reaction['errorB'])) {
            throw new Exception(implode(',', $reaction['errorB']) . self::NOT_MATCH . implode(',', $reaction['errorA']));
        } elseif (isset($reaction['errorB'])) {
            throw new Exception(self::ELEMENTS_IS_BEFORE_BUT_AFTER_NOT . implode(',', $reaction['errorB']));
        } elseif (isset($reaction['errorA'])) {
            throw new Exception(self::ELEMENTS_IS_AFTER_BUT_BEFORE_NOT . implode(',', $reaction['errorA']));
        }
        if (count($reaction['substances']) > 20) {
            throw new Exception(self::TO_MANY_SUBSTANCES);
        }


        // обход всех веществ
        foreach ($reaction['substances'] as $i => $v) {
            // поиск скобок
            $this->findBrackets($v);

            $arrayMatrix[count($reaction['elements'])][$i] = $this->brackets['charge'];

            if ($this->brackets['charge'] != 0) {
                $chargedSubstance++;
            }

            /* Реакция на признак электрона */
            if ($reaction['substances'][$i] == 'e') {
                $this->brackets['charge'] = -1;
                $arrayMatrix[count($reaction['elements'])][$i] = $this->brackets['charge'];
                $chargedSubstance++;
                continue;
            }

            /* Синтаксический разбор по элементам */
            $this->findIndexesS($arrayMatrix, $reaction, $i);

            /* Выделение индекса */
        }

        /***** VI. ПРОВЕРКА ЗАКОНА СОХРАНЕНИЯ ЗАРЯДА
         * ПЕРЕНОС ЗАРЯДОВ В ОБЩУЮ МАТРИЦУ   ***/
        if ($chargedSubstance == 1) throw new Exception('Только одна заряженная частица');

        $length = count($reaction['substances']) - 1;
        $nelem = count($reaction['elements']);

        if ($chargedSubstance) $nelem++;

        /***** VII. ПРИМЕНЕНИЕ МЕТОДА ГАУССА *****/
        $gaussAnswer = $this->gauss($arrayMatrix, $length, $nelem, $arrayX);

        if ($gaussAnswer == self::GAUSS_NOSOL) throw new Exception("Эту реакцию нельзя уравнять");
        if ($gaussAnswer == self::GAUSS_MANYSOL) throw new Exception("Реакцию можно уравнять бесконечным числом способов");

        $arrayX[$length] = 1;

        /***** VIII. ПРИВЕДЕНИЕ КОРНЕЙ К ЦЕЛОЧИСЛЕННОМУ ВИДУ *****/
        for ($i = 1; $i <= 2000; $i++) { // чем выше число тем больше может быть коэффициент
            /* Проверка коэфф i на пригодность */
            $k = 1;
            for ($j = 0; $j <= $length; $j++) {
                $ta = $arrayX[$j] * $i;
                $tb = round($ta) - $ta;
                if ($this->cmpzero($tb)) {
                    $k = 0;
                    break;
                }
            }
            /* Собственно домножение */
            if ($k == 1) {
                for ($j = 0; $j <= $length; $j++) {
                    $arrayX[$j] *= $i;
                }
                break;
            }
        }

        /***** IX. ВЫВОД ОТВЕТА *****/
        /* Соединения с отриц. коэффициентами - продукты
          с положительными - исходные */
        if ($arrayX[0] < 0) {
            $k = -1;
        } else {
            $k = 1;
        }
        for ($i = 0; $i <= $length; $i++) {
            $arrayX[$i] *= $k;
        }

        $s = '';

        /* а) Вывод исходных веществ */
        $j = 0; /* Если 0 - вещ-во не встречалось */


        for ($i = 0; $i <= $length; $i++) {
            /* Вывод соединения i с коэффициентом */
            if ($arrayX[$i] <= 0)
                continue;

            if ($i > 0 && $j)
                $s .= ' + '; /* Вывод знака - разделителя */

            $j++;

            if ($this->cmpzero($arrayX[$i] - 1)) {
                $xIround = $arrayX[$i];
                $s .= round($xIround);
            }
            $s .= $reaction['substances'][$i];
        }
        $s .= ' = '; /* Вывод знака равенства */

        /* б) Вывод продуктов */
        $j = 0; /* Если 0 - вещ-во не встречалось */
        for ($i = 0; $i <= $length; $i++) {
            /* Вывод соединения i с коэффициентом */
            if ($arrayX[$i] >= 0){
                continue;
            }

            if ($i > 0 && $j) $s .= ' + '; /* Вывод знака - разделителя */

            $j++;

            if ($this->cmpzero(-$arrayX[$i] - 1)) {
                $xIround = -$arrayX[$i];
                $s .= round($xIround);
            }
            $s .= $reaction['substances'][$i];
        }

        $s = str_replace('{1}', '{+}', $s);
        $s = str_replace(['{-1}', '{1-}'], '{-}', $s);
        $s = preg_replace('/\{\+?(\d)\+?\}/', '{$1+}', $s);
        $s = preg_replace('/\{-?(\d)-?\}/', '{$1-}', $s);

        return $s;
    }

    public function getTestedFormula()
    {
        $chargedSubstance = 0;
        $charge = [0,0];

        foreach ($this->arrbeforeRaw as $i => $item) {
            $this->findBrackets($item);

             if ($this->brackets['charge'] != 0){
                 $charge[0] += ((int)$item?:1) * $this->brackets['charge'];

                 $chargedSubstance++;
             }

            /* Реакция на признак электрона */
            if ($this->arrbefore[$i] == 'e') {
                $charge[0] += ((int)$item?:1) * -1;

                $chargedSubstance++;
                continue;
            }

            $this->findIndexes(true);
        }

        foreach ($this->arrafterRaw as $i => $item) {
            $this->findBrackets($item);

            if ($this->brackets['charge'] != 0){
                $charge[1] += ((int)$item?:1) * $this->brackets['charge'];
                $chargedSubstance++;
            }

            /* Реакция на признак электрона */
            if ($this->arrafter[$i] == 'e') {
                $charge[1] += ((int)$item?:1) * -1;
                $chargedSubstance++;
                continue;
            }

            $this->findIndexes();
        }

        $arr = [];

        foreach ($this->elements['elements'] as $item) {

            if ($this->elements['array'][$item][0] == $this->elements['array'][$item][1]){
                continue;
            }
            $arr[] = $item;

        }

        if ( $chargedSubstance == 1 ) {
            throw new Exception('Только одна заряженная частица.');
        }
        if ( $charge[0] != $charge[1] )  {
            throw new Exception('Ошибка заряда.');
        }

        if (count($arr) == 1) {
            throw new Exception('Елемент: ' . $arr[0] . ' не сошелся.');
        } else if (count($arr) > 1) {
            throw new Exception('Елементы: ' . implode(',', $arr) . ' не сошлись.');
        } else {
            return 1;
        }
    }
}
