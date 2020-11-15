require 'csv'

def arch_file(fn)
  File.join(File.dirname(__FILE__), 'archive', fn)
end

emails = Hash.new

files = Dir.entries(File.join(File.dirname(__FILE__), 'archive')).find_all{ |d| d.match(/^members/) }.sort

CSV.foreach(arch_file(files.shift), encoding: "ISO8859-1") { |row| emails[row[0]] = row[11] }

files.each do |file|
  puts file
  CSV.foreach(arch_file(file), encoding: "ISO8859-1") do |row|
    if row[11] != emails[row[0]]
      puts "  #{row[4]} #{row[3]}, old: #{emails[row[0]]}, new: #{row[11]}" if emails[row[0]] && emails[row[0]].length > 0
      emails[row[0]] = row[11]
    end
  end
end
