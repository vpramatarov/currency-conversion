# OpenTag Interview Task

## Exchange rates service

### Problem definition
We need to implement a new currency conversion service. That new project will face a number of 
API consumers that will request exchange rates.

### Requirements
* The API client provides two ISO currency codes and receives their exchange rate
    - Example: When we provide `CAD CHF` codes we expect to receive `0.73`
* Additionally, the exchange rate should contain a sign indicating the rate trend. The trend should be calculated as the deviation between the current rate and average of the last 10 rates (calculation should be simple, avoid usage of statistics formulas). A negative, positive or static sign is then added as a suffix to the exchange rate value
    - Example `0,73 ↑` or `0,73 ↓` or `0,73 -`

### Must haves
* Code in PHP, ideally using the Symfony framework
* You're free to choose any storage mechanism you wish. We expect to be able to run the application locally
  by using `docker-compose`, with no local dependencies required.

### Nice to haves
* Full tests coverage
* UI

### Bonus points
* Imagine that you call a third-party API to get the exchange rate between two currency pairs every hour. 
  Service A calls you for the following currency pair `CAD CHF`, and 10 seconds later Service B calls you for the same rate.
  * Can you minimise the networking calls to the third party for repeat pair exchange rates, within the 1-hour window?
  * Can you optimise the n-th call latency within a single currency pair and within a 1-hour window?  
  
