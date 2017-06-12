# chemical_reactions

  Code is based on c_urav 2.0 by Восков Алексей and translated from c++ to javascript and php

# js code for balance chemical reaction use:

# first paramether is reaction, second if you need to clear it from coefficients

  calculateReactionsCoef('2H2O + 2K = 2KOH + H2', true); // > 2H2O + 2K = 2KOH + H2

  calculateReactionsCoef('H2O + K = KOH + H2'); // > 2H2O + 2K = 2KOH + H2

# For test if the reaction is ballanced use

  getTestedFormula('2H2O + 2K = 2KOH + H2'); // > true

  getTestedFormula('2H2O + 2K = KOH + H2'); // > Error with explanation


# if you need to calculate id of substance you can use

  getIdSubstanse('CH3COOH'); // > C2H4O2

# For php code create object you need 

  $s = new Substance('Al(OH)3');

  echo $s->getId(); // > AlH3O3

# Reaction

  $r = new Reaction('H2O + K = KOH + H2');

  $r->getTestedFormula(); // 1 or Exception

  $r->calculateReactionsCoef(); // > 2H2O + 2K = 2KOH + H2
