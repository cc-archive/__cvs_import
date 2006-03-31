import os
import sys
import glob

source_files = glob.glob(sys.argv[-1] + '*.adp')

print source_files
print

for filename in source_files:

    dest_file = filename.replace('.adp', '.pt')
    print 'renaming %s to %s...' % (filename, dest_file)
    os.rename(filename, dest_file)

