# MySQL query to retrieve flight counts by category and year from IACCDB
# DJM, 2019-07-16

SELECT category, YEAR(start) AS yr, COUNT(pilot_flights.id) AS flights
  FROM contests, flights, categories, pilot_flights
  WHERE contest_id = contests.id
    AND category_id = categories.id
    AND pilot_flights.flight_id = flights.id
    AND YEAR(start) < YEAR(NOW())
  GROUP BY category, yr
  ORDER BY category, yr
