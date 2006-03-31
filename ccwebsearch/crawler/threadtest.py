#!/usr/bin/python

import threading

class ben:
    def __call__(self):
        for i in range(100):
            print "One Num: %d" %i


threads= []
runner= ben()
for i in range(10):
    one_thread= threading.Thread(target=runner)
    threads.append(one_thread)

for t in threads:
    t.start()
