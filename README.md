# chemical_reactions
code based on c_urav 2.0 by Восков Алексей and translated from c++ to javascript and php

js code for balance chemical reaction use:
first paramether is reaction, second if you need to clear it from coefficients

calculateReactionsCoef('2H2O + 2K = 2KOH + H2', true);
calculateReactionsCoef('H2O + K = KOH + H2');


for test if the reaction is ballanced use
getTestedFormula('2H2O + 2K = 2KOH + H2'); // > true
getTestedFormula('2H2O + 2K = KOH + H2'); // > false


if you need to calculate id of substance you can use
getIdSubstanse('CH3COOH'); // > C2H4O2
