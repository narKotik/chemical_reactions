var regexp1 = /(A[cglmrstu]|B[aehikr]?|C[adeflmnorsu]?|D[bsy]?|E[rsu]|F[emflr]?|G[ade]|H[efgos]?|I[nr]?|Kr?|L[airvu]|M[cdgnot]|N[abdehiop]?|O[sg]?|P[abdmortu]?|R[abefghnu]|S[bcegimnr]?|T[abcehilms]?|U|V|W|Xe|Yb?|Z[nr])/g;
var regexp2 = /(A[cglmrstu]|B[aehikr]?|C[adeflmnorsu]?|D[bsy]?|E[rsu]|F[emflr]?|G[ade]|H[efgos]?|I[nr]?|Kr?|L[airvu]|M[cdgnot]|N[abdehiop]?|O[sg]?|P[abdmortu]?|R[abefghnu]|S[bcegimnr]?|T[abcehilms]?|U|V|W|Xe|Yb?|Z[nr]|\d{1,4}|\[|\]|\(|\)|\*)/g;
var regexp3 = /(A[cglmrstu]|B[aehikr]?|C[adeflmnorsu]?|D[bsy]?|E[rsu]|F[emflr]?|G[ade]|H[efgos]?|I[nr]?|Kr?|L[airvu]|M[cdgnot]|N[abdehiop]?|O[sg]?|P[abdmortu]?|R[abefghnu]|S[bcegimnr]?|T[abcehilms]?|U|V|W|Xe|Yb?|Z[nr]|\d{1,4}|\[|\]|\(|\)|\*|\{|\}|e|-)/g;

function calculateReactionsCoef(str, coefClr){
  var GAUSS_ACCURACY = 0.0000001;
  var ACCURACY = GAUSS_ACCURACY;
  var GAUSS_OK = 0;
  var GAUSS_NOSOL = 1;
  var GAUSS_MANYSOL = 2;
  var chargedSubstance = 0; // заряженных частиц
  var arrayMatrix = (function(){
    var arrA = [];
    for(var i = 0; i < 20; i++){
      arrA[i] = Array();
      for(var j = 0; j < 20; j++){
        arrA[i][j] = 0;
      }
    }
    return arrA;
  }());
  var arrayX = new Array(20);
  if(coefClr){
    str = clearFromCoefficient(str, true);
  }
  str = str.replace(/\s/g, '');
  var regExpElements = regexp1;

  if(!~str.indexOf('=')) throw Error('Нет знака равно "="');
  if( str.indexOf('=') - str.lastIndexOf('=') != 0) throw Error('Два знака равно "="');

  var brackets;
  var reaction = varietyOfElements(str);
  str = str.replace(/\{\+?(\d{1,2})\+?\}/g, '{$1}');
  str = str.replace('/(–|−)/g', '-');
  str = str.replace(/\{\-\}/g, '{1-}');
  str = str.replace(/\{\+\}/g, '{1}');
  reaction.substances = str.split(/\+|\=/);

  // проверка синтаксиса
  if(reaction.errorA && reaction.errorB){
    throw Error(reaction.errorB + ' != ' + reaction.errorA);
  }else if(reaction.errorB){
    throw Error('Елементы есть до = но нет после: ' + reaction.errorB);
  }else if(reaction.errorA){
    throw Error('Елементы есть после = но нет до: ' + reaction.errorA);
  }
  if(reaction.substances.length > 20) throw new Error('Слишком много соединений.');

  // обход всех веществ
  for(var i = 0; i < reaction.substances.length; i++){
    // поиск скобок


    findBrackets(reaction.substances[i]);

    arrayMatrix[reaction.elements.length][i] = brackets['charge'];
    if (brackets['charge'] != 0) chargedSubstance++;
    /* Реакция на признак электрона */
    if(reaction.substances[i] == 'e'){
      brackets['charge'] = -1;
      arrayMatrix[reaction.elements.length][i] = brackets['charge'];
      chargedSubstance++;
      continue;
    }

    /* Синтаксический разбор по элементам */
    for(var j = 0; j < reaction.arrOfElements.length; j++){
      var item = reaction.arrOfElements[j];
      var indexOfElement = 1;
      if(/(\d{1,4}|\(|\)|\[|\]|\*|-|\{|\})/.test(item)){
        continue;
      }

      indexOfElement *= (isFinite(reaction.arrOfElements[j + 1]))?reaction.arrOfElements[j + 1] : 1

      if(brackets.anchor){
        // если были скобки то пересчитываем индекс
        for(var k = 0; k < brackets.br.length; k+=2){
          for(var l = 0; l < brackets[k].length; l++){
            if(k < brackets.br.length-1 && j > brackets[k][l] && j < brackets[k + 1][l]){
              var x = (isFinite(reaction.arrOfElements[brackets[k + 1][l] + 1])) ? reaction.arrOfElements[brackets[k + 1][l] + 1] : 1; // индекс после скобки
              indexOfElement *= x;
            }else if(brackets.br[k] == '*' && j > brackets[k][l]){
              if(brackets[k].length == 1){
                var x = (isFinite(reaction.arrOfElements[brackets[k][l]+1])) ? reaction.arrOfElements[brackets[k][l]+1] : 1; // индекс после *
                indexOfElement *= x;
              }else{
                if(j < brackets[k][l+1]){
                  var x = (isFinite(reaction.arrOfElements[brackets[k][l]+1])) ? reaction.arrOfElements[brackets[k][l]+1] : 1; // индекс после *
                  indexOfElement *= x;
                }else{
                  var x = (isFinite(reaction.arrOfElements[brackets[k][l+1]+1])) ? reaction.arrOfElements[brackets[k][l+1]+1] : 1; // индекс после *
                  indexOfElement *= x;
                }
              }
            }
          }
        }
      }
      //заносим значение в матрицу
      var n = reaction.elements.indexOf(item);
      arrayMatrix[n][i] += indexOfElement;
    }
    /* Выделение индекса */

}
if (chargedSubstance == 1) throw new Error('только одна заряженная частица');
/***** VI. ПРОВЕРКА ЗАКОНА СОХРАНЕНИЯ ЗАРЯДА
           ПЕРЕНОС ЗАРЯДОВ В ОБЩУЮ МАТРИЦУ   ***/

/***** VII. ПРИМЕНЕНИЕ МЕТОДА ГАУССА *****/
var length = reaction.substances.length-1;
var nelem = reaction.elements.length;
if(chargedSubstance) {
  nelem++;
}

var gaussAnswer = gauss(arrayMatrix, length, nelem, arrayX); //по ходу этот массив можно создать в самом гаусе

if(gaussAnswer == GAUSS_NOSOL) throw new Error('Эту реакцию нельзя уравнять');
if(gaussAnswer == GAUSS_MANYSOL) throw new Error('Реакцию можно уравнять бесконечным числом способов');

arrayX[length] = 1;
/***** VIII. ПРИВЕДЕНИЕ КОРНЕЙ К ЦЕЛОЧИСЛЕННОМУ ВИДУ *****/
for (var i = 1; i <= 2000; i++){ // чем выше число тем больше может быть коэффициент
  /* Проверка коэфф i на пригодность */
  var k = 1;
  for (j = 0; j <= length; j++){ // меньше равно и минус 1
      var ta = arrayX[j] * i;
      var tb = Math.round(ta) - ta;
      if (cmpzero(tb)) {
        k = 0;
        break;
      }
  }
  /* Собственно домножение */
  if (k == 1) {
  for (j = 0; j <= length; j++) arrayX[j] *= i;
      break;
  }
}

/***** IX. ВЫВОД ОТВЕТА *****/
   /* Соединения с отриц. коэффициентами - продукты
      с положительными - исходные */
if (arrayX[0] < 0){ var k = -1; }
else{ k = 1;}
for (var i = 0; i <= length; i++){ arrayX[i] *= k; }

var s = '', xIround;
/* а) Вывод исходных веществ */
j = 0; /* Если 0 - вещ-во не встречалось */
for (i = 0; i <= length; i++){
  /* Вывод соединения i с коэффициентом */
  if (arrayX[i] <= 0) continue;

  if (i > 0 && j) s +=  ' + '; /* Вывод знака - разделителя */

  j++;

  if (cmpzero(arrayX[i] - 1.0)){
      xIround = arrayX[i];
      s += Math.round(xIround);
  }
  s += reaction.substances[i];
}
s += ' = '; /* Вывод знака равенства */
/* б) Вывод продуктов */
j = 0; /* Если 0 - вещ-во не встречалось */
for (i = 0; i <= length; i++){
/* Вывод соединения i с коэффициентом */
if (arrayX[i] >= 0) continue;
if (i > 0 && j) s += ' + '; /* Вывод знака - разделителя */
j++;
if (cmpzero(-arrayX[i] - 1.0)){
    xIround = -arrayX[i];
    s += Math.round(xIround);
}
s += reaction.substances[i];
}
s = s.replace(/\{1\}/g, '{+}');
s = s.replace(/\{\+?(\d)\+?\}/g, '{$1\+}');
s = s.replace(/\{-?1-?\}/g, '{-}');
s = s.replace(/\{-?(\d)-?\}/g, '{$1-}');

return s;



function findBrackets(sub){
  reaction.arrOfElements = sub.match(regexp3);
  if(!/(\(|\)|\[|\]|\*|\{|\})/.test(sub)){
    brackets = {
      anchor: false,
      charge: 0
    };
    return;
  }
  brackets = {
    br: ['(', ')', '[', ']', '*'],
    0: [],
    1: [],
    2: [],
    3: [],
    4: [],
    anchor: true,
    charge: 0
  };

  for(var i = 0; i < brackets.br.length; i++){
    var j = -1;
    do{
      j = reaction.arrOfElements.indexOf(brackets.br[i], j+1);
      if(j > -1){
        brackets[i].push(j);
        brackets.anchor = true;
      }
    }while(j > -1);
  }
  // проверка синтаксиса скобок
  for(var i = 0; i < brackets.br.length-1; i+=2){
      if(brackets[i].length > brackets[i+1].length){
        throw Error('Нет закрывающей скобки у ' + sub);
      }else if(brackets[i].length < brackets[i+1].length){
        throw Error('Нет открывающей скобки у ' + sub);
      }
    // этот цикл можно и убрать. нет проверки если одна скобка перекрывает другую
    for(var j = 0; j < brackets[i].length; j++){
      if(brackets[i][j] > brackets[i+1][j]){
        throw Error('Ошибка со скобками ' + sub)
      }
    }
  }

  // получение заряда вещества
  /*сюда добавить выделение заряда*/
  //var charge = '';
  if(sub.indexOf('{') > -1){
      var charge = sub.slice(sub.indexOf('{') + 1, sub.indexOf('}'));
      if(charge.indexOf('-') > -1 && parseInt(charge) > 0){
        brackets['charge'] = parseInt(charge) * -1;
      }else{
        brackets['charge'] = parseInt(charge);
      }
  }

}
function varietyOfElements(str){
    var strArr = str.split('=');
    var answer = {};
    var arrB = strArr[0].match(regExpElements);
    var arrA = strArr[1].match(regExpElements);
    var objB = {};
    var objA = {};
    var arrAB = [],
        arrBA = []; //есть до но отсутствует после
    var obj = {};
    for(var i = 0; i < arrB.length; i++){
      if(objB[arrB[i]]) continue;

      objB[arrB[i]] = true;
      obj[arrB[i]] = true;
    }
    for(var i = 0; i < arrA.length; i++){
      if(objA[arrA[i]]) continue;

      objA[arrA[i]] = true;
      obj[arrA[i]] = true;
    }
    arrB = Object.keys(objB);
    arrA = Object.keys(objA);
    var length = Math.max(arrB.length, arrA.length);
    for(var i = 0; i < length; i++){
      if(arrB.indexOf(arrA[i]) == -1 && arrA[i]) arrAB.push(arrA[i]);
      if(arrA.indexOf(arrB[i]) == -1 && arrB[i]) arrBA.push(arrB[i]);
    }

    answer.elements = Object.keys(obj);
    if(arrAB.length){
      answer.errorA = arrAB;
    }
    if(arrBA.length){
      answer.errorB = arrBA;
    }
    return answer;

  }

function gauss(a, n, u, x){
  var i, j, k; /* Счетчики циклов */
  var sn; /* Номер строки */
  var d; /* Коэффициент домножения или модуль наиб. эл. */

  /*** Проверка u и n ***/
  if (n > u) return 2;
  /*** Приведение к диагональному виду ***/
  for (j = 0; j < n; j++){
    /* а) поиск строки с наибольшим по модулю элементом */
    d = Math.abs(a[j][j]); sn = j;
    for (i = j; i < u; i++){
      if (Math.abs(a[i][j]) > d){
        d = Math.abs(a[i][j]);
        sn = i;
      }
    }
    /* б) перенос строки на надлежащее место */
    for (k = 0; k <= n; k++){
      d = a[sn][k];
      a[sn][k] = a[j][k];
      a[j][k] = d;
    }

    /* в) деление ведущего ур-я на главный элемент */
    d = a[j][j];

    if (d)
      for (k = 0; k <= n; k++){ a[j][k] = a[j][k] / d;}
    else
      for (k = 0; k <= n; k++){ a[j][k] = 0;}

    /* г) вычитание данной строки из всех остальных */
    /*    с домножением на коэффициент */
    for (i = 0; i < u; i++){
      if (i == j) continue;  /* Не трогаем вычит. строку */
      d = -a[i][j];
      for (k = 0; k <= n; k++){ /* Вычитание */
          a[i][k] = a[i][k] + a[j][k] * d;
      }
    }
  }
   /*** Вычисление корней ***/
    /* а) проверка системы на разрешимость */
  if (u > n){
    //i от 3 до 5 должно быть
    for (i = n; i < u; i++){
      k = 0;
      // j  от 0 до 3
      for (j = 0; j < n; j++)
          if (Math.abs(a[i][j]) > GAUSS_ACCURACY) k = 1;
      if (k == 0 && Math.abs(a[i][j]) > GAUSS_ACCURACY) return 1; //Math.abs(a[i][n])
    }
  }

  /* б) поиск корней */
  for (i = 0; i < n; i++){
    x[i] = -a[i][n];
    if (a[i][i] != 1){
    /** Обработка ошибок **/
      if (x[i])
        return GAUSS_NOSOL; /* Решений нет */
      else
        return GAUSS_MANYSOL; /* Бесконечно много решений */
    }
    if (Math.abs(x[i]) < GAUSS_ACCURACY) x[i] = 0; /* Обнуление слишком малых знач. */
  }
  return GAUSS_OK; /* Нормальное завершение работы */
}

function cmpzero(x){
  return (Math.abs(x) > ACCURACY);
}

}

function getIdSubstanse(subst){
    subst = subst.replace(/\s/g, '');
    var brackets;
    var elements = {
      elements:[],
      array:[]
    };

    findBrackets(subst);

    findIndexes();

    var arr = [];

    for(var i in elements.array){
      index = (elements.array[i] > 1) ? elements.array[i] : '';
      arr.push(i + index);
    }

    return arr.sort().join('');



  function findIndexes(b){
    for(var j = 0; j < elements.arrOfElements.length; j++){
      var item = elements.arrOfElements[j];
      var indexOfElement = 1;
      var firstIndex = 1;
      if(isFinite(elements.arrOfElements[0])){
        firstIndex = indexOfElement *= elements.arrOfElements[0];
      }

      if(/(\d{1,8}|\(|\)|\[|\]|\*)/.test(item)){
        continue;
      }

      indexOfElement *= (isFinite(elements.arrOfElements[j + 1])) ? elements.arrOfElements[j + 1] : 1

      if(brackets.anchor){
          // если были скобки то пересчитываем индекс
          for(var k = 0; k < brackets.br.length; k+=2){
            for(var l = 0; l < brackets[k].length; l++){
              if(k < brackets.br.length-1 && j > brackets[k][l] && j < brackets[k + 1][l]){
                var x = (isFinite(elements.arrOfElements[brackets[k + 1][l] + 1])) ? elements.arrOfElements[brackets[k + 1][l] + 1] : 1; // индекс после скобки
                indexOfElement *= x;
              }else if(brackets.br[k] == '*' && j > brackets[k][l] ){
                if(brackets[k].length == 1){
                  var x = (isFinite(elements.arrOfElements[brackets[k][l]+1]))? elements.arrOfElements[brackets[k][l]+1] : 1; // индекс после *
                  indexOfElement *= x // firstIndex; // тут
                }else{
                  if(j < brackets[k][l+1]){
                    var x = (isFinite(elements.arrOfElements[brackets[k][l]+1]))? elements.arrOfElements[brackets[k][l]+1] : 1; // индекс после *
                    indexOfElement *= x // firstIndex; // тут
                  }else{
                    var x = (isFinite(elements.arrOfElements[brackets[k][l+1]+1]))? elements.arrOfElements[brackets[k][l+1]+1] : 1; // индекс после *
                    indexOfElement *= x // firstIndex; // тут
                  }
                }
              }
            }
          }
        }
      // если такого элемента нет добавляем его в массив
      if(elements.elements.indexOf(item) == -1){
        index = elements.elements.push(item);
        elements.array[item] = 0;
      }

      elements.array[item] += indexOfElement;
    }
  } // конец findIndexes


  function findBrackets(sub){
    sub = sub.replace(/\(([A-Z][a-z]?)\)/g, '$1');
    sub = sub.replace(/\(\)/g, '');
    elements.arrOfElements = sub.match(regexp2);

    if(!/(\(|\)|\[|\]|\*)/.test(sub)){
      brackets = {anchor: false};
      return;
    }

    brackets = {
      'br': ['(', ')', '[', ']', '*'],
      'dept':[0,,0],
      0: [],
      1: [],
      2: [],
      3: [],
      4: [],
      anchor: true,
    };

    for (var i = 0; i < elements.arrOfElements.length; i++){
      var item = elements.arrOfElements[i];
      var x;
      switch(item){
        case '(':
        case '[':
          x = brackets['br'].indexOf(item);
          brackets['dept'][x] += 1;
          if(brackets[x][brackets['dept'][x]-1] == undefined){
            brackets[x][brackets['dept'][x]-1] = i;
          }else if(brackets[x][brackets['dept'][x]-1 + 10] == undefined){
            brackets[x][brackets['dept'][x]-1 + 10] = i;
          }else{
            brackets[x].push(i);
          }
          break;
        case ')':
        case ']':
          x = brackets['br'].indexOf(item);
          brackets['dept'][x-1] -= 1;
          if(brackets[x][brackets['dept'][x - 1]] == undefined){
            brackets[x][brackets['dept'][x - 1]] = i;
          }else if(brackets[x][brackets['dept'][x - 1] + 10] == undefined){
            brackets[x][brackets['dept'][x - 1] + 10] = i;
          }else{
            brackets[x].push(i);
          }
          break;
        case '*':
          x = brackets['br'].indexOf(item);
          brackets[x].push(i);
      }
    }

    if(brackets['dept'][0] > 0 || brackets['dept'][1] > 0){
        throw new Error('Недостаточно закрывающих скобок');
    }
    if(brackets['dept'][0] < 0 || brackets['dept'][1] < 0){
        throw new Error('Недостаточно открывающих скобок');
    }
    for(var i = 0; i < brackets['br'].length; i++){
      var arr = [];
      for(var j in brackets[i]){
        arr.push(brackets[i][j]);
      }
      brackets[i] = arr;
    }

    delete(brackets['dept']);
  } // конец бракетс

}

function getTestedFormula(str){
  var brackets;
  var elements = {};
  str = clearFromCoefficient(str);
  str = str.replace(/\s/g, '');
  elements.stringBefore = str.slice(0, str.search(/(=|→)/));
  elements.stringAfter = str.slice(str.search(/(=|→)/) + 1);
  elements.substancesBefore = elements.stringBefore.split('+');
  elements.substancesAfter = elements.stringAfter.split('+');
  elements.elements = [];
  var index;

  for(var i = 0; i < elements.substancesBefore.length; i++){
    var item = elements.substancesBefore[i];
    findBrackets(item);

    findIndexes(true);
  }
  for(var i = 0; i < elements.substancesAfter.length; i++){
    var item = elements.substancesAfter[i];
    findBrackets(item);

    findIndexes();
  }

  var arr = [];
  for(var i = 0; i < elements.elements.length; i++){
    var item = elements.elements[i];
    if(elements[item][0] == elements[item][1]) continue;
    arr.push(item);
  }
  if(arr.length == 1){
    throw Error('Елемент: ' + arr.toString() + ' не сошелся.');
  }else if(arr.length > 1){
    throw Error('Елементы: ' + arr.toString() + ' не сошлись.');
  }else{
    return true;
  }
function findIndexes(b){
  for(var j = 0; j < elements.arrOfElements.length; j++){
    var item = elements.arrOfElements[j];
    var indexOfElement = 1;

    if(isFinite(elements.arrOfElements[0])){
      indexOfElement *= elements.arrOfElements[0];
    }

    if(/(\d{1,4}|\(|\)|\[|\]|\*)/.test(item)){
      continue;
    }

    indexOfElement *= (isFinite(elements.arrOfElements[j + 1])) ? elements.arrOfElements[j + 1] : 1

    if(brackets.anchor){
      // если были скобки то пересчитываем индекс
      for(var k = 0; k < brackets.br.length; k+=2){
        for(var l = 0; l < brackets[k].length; l++){
          if(k < brackets.br.length-1 && j > brackets[k][l] && j < brackets[k + 1][l]){
            var x = (isFinite(elements.arrOfElements[brackets[k + 1][l] + 1])) ? elements.arrOfElements[brackets[k + 1][l] + 1] : 1; // индекс после скобки
            indexOfElement *= x;
          }else if(brackets.br[k] == '*' && j > brackets[k][l]){
            if(brackets[k].length == 1){
              var x = (isFinite(elements.arrOfElements[brackets[k][l]+1])) ? elements.arrOfElements[brackets[k][l]+1] : 1; // индекс после *
              indexOfElement *= x;
            }else{
              if(j < brackets[k][l+1]){
                var x = (isFinite(elements.arrOfElements[brackets[k][l]+1])) ? elements.arrOfElements[brackets[k][l]+1] : 1; // индекс после *
                indexOfElement *= x;
              }else{
                var x = (isFinite(elements.arrOfElements[brackets[k][l+1]+1]))? elements.arrOfElements[brackets[k][l+1]+1] : 1; // индекс после *
                indexOfElement *= x;
              }
            }
          }
        }
      }
    }
    if(b){
      index = elements.elements.indexOf(item);
      if(index == -1){
        index = elements.elements.push(item);
        elements[item] = [0,0];
      }

      elements[item][0] += indexOfElement;
    }else{
      elements[item][1] += indexOfElement;
    }
  }
}


  function findBrackets(sub){
    elements.arrOfElements = sub.match(regexp2);
    if(!/(\(|\)|\[|\]|\*)/.test(sub)){
      brackets = {anchor: false};
      return;
    }
    brackets = {
      br: ['(', ')', '[', ']', '*'],
      0: [],
      1: [],
      2: [],
      3: [],
      4: [],
      anchor: false,
    };

    for(var i = 0; i < brackets.br.length; i++){
      var j = -1;
      do{
        j = elements.arrOfElements.indexOf(brackets.br[i], j+1);
        if(j > -1){
          brackets[i].push(j);
          brackets.anchor = true;
        }
      }while(j > -1);
    }
  } // конец бракетc
} // конец всего
