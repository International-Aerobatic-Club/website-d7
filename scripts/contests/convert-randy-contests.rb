# DJM, 2015-03-26

require 'csv'

class String
  def unlabel
    self.sub(/^[A-Z][A-Za-z ]+: /, '')
  end
end


symbols = [ :title, :airport_id, :airport_name, :city, :state, :region, :practice_start, :practice_end,
  :contest_start, :contest_end, :weather_start, :weather_end, :contest_director, :contact_info, :comments ]

Struct.new("Contest", *symbols)

contests = Array.new

while !STDIN.eof? do

  line = readline.chomp

  if line.match(/^Name: /)
    contests << Struct::Contest.new
    contests.last[:title] = line.unlabel.sub(/ \(.*/, '')
    contests.last[:region] = line.unlabel.sub(/^.*\(/, '').sub(/\)/, '')
  elsif line.match(/^Dates: /)
    contests.last[:contest_start], contests.last[:contest_end] = line.unlabel.split(' - ')
  elsif line.match(/^PracticeRegistration: /)
    contests.last[:practice_start], contests.last[:practice_end] = line.unlabel.split(' - ')
  elsif line.match(/^RainWeather: /)
    contests.last[:weather_start], contests.last[:weather_end] = line.unlabel.split(' - ')
  elsif line.match(/^Location: /)
    loc = line.unlabel
    contests.last[:airport_id] = loc.sub(/^.*\(/, '').sub(/\): .*$/, '')
    contests.last[:airport_name] = loc.sub(/ \(.*$/, '')
    contests.last[:city] = loc.sub(/^.*: /, '')
    contests.last[:state] = loc.sub(/^.*, /, '')
  elsif line.match(/^Region: /)
    contests.last[:region] = line.unlabel
  elsif line.match(/^Contest Director: /)
    contests.last[:contest_director] = line.unlabel
  elsif line.match(/^Contact Information: /)
    contests.last[:contact_info] = line.unlabel
  elsif line.match(/^Website: /)
    contests.last[:contact_info] ||= ""
    contests.last[:contact_info] += " / " + line.unlabel
  elsif line.match(/^Comments: /)
    contests.last[:comments] = line.unlabel
  end

end


puts symbols.map { |sym| sym.to_s }.join(',')

contests.each do |c|

  a = Array.new

  symbols.each do |sym|
    a << (c[sym].nil? ? '' : ('"' + c[sym] + '"'))
  end

  puts a.join(',')

end
