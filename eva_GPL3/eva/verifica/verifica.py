#
#    This file is part of Eva tool.
#
#    Eva is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    Eva is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with Eva. If not, see <http://www.gnu.org/licenses/>.
#
#    For commercial use of Eva, please contact me.
#
#    COPYRIGHT 2010-2013  - Otavio A. B. Penatti - otavio_at_penatti_dot_com
#

# -*- coding: utf-8 -*-
#!/usr/bin/python

import os
from os.path import join
import sys
from ctypes import *
import traceback
import atexit

def response_status(status, nomeArqResults):
   fd = open(nomeArqResults,"w")
   fd.write(str(status))
   fd.close()
   sys.exit(status)


def cleanup():
   try:
       os.remove("/tmp/house_bin"+sys.argv[1]+".txt")
   except OSError:
       pass

   try:
       os.remove("/tmp/car"+sys.argv[1]+".txt")
   except OSError:
       pass


atexit.register(cleanup)

fullbasepath = "descriptors"
nomeArqResults = "/tmp/resultado_verifica_"+sys.argv[1]+".txt"


try:
    lib = CDLL(join(fullbasepath,sys.argv[1]))
except OSError, ex:
    #print "Invalid file!\n", str(ex)
    traceback.print_tb(sys.exc_info()[2])
    print ex
    if (str(ex)[-6:] == 'header'):
        print "<i>Incompatible file type</i>"
    else:
        print "<i>Compilation problem</i>"
    response_status(6, nomeArqResults)
except:
    print "<i>Error!!</i>"
    response_status(7, nomeArqResults)


try:
    lib.Extraction
except AttributeError:
    print "Extraction function is essential, but it was not found or it has invalid header."
    response_status(1,nomeArqResults)

try:
    lib.Distance
except AttributeError:
    print "Distance function is essential, but it was not found or it has invalid header."
    response_status(2,nomeArqResults)


lib.Extraction.restype = c_void_p

try:
    lib.Extraction("images/house_bin.ppm","/tmp/house_bin"+sys.argv[1]+".txt")
except:
    print "Error while extracting from house_bin.ppm."    
    response_status(3,nomeArqResults)	

print "Vector from house_bin.ppm"
print open("/tmp/house_bin"+sys.argv[1]+".txt").read()
sys.stdout.flush()

try:
    lib.Extraction("images/car.ppm","/tmp/car"+sys.argv[1]+".txt")
except:
    print "Error while extracting from car.ppm."    
    response_status(4,nomeArqResults)	

print "Vector from car.ppm"
print open("/tmp/car"+sys.argv[1]+".txt").read()
sys.stdout.flush()


lib.Distance.restype = c_double
try:
    distance = lib.Distance("/tmp/house_bin"+sys.argv[1]+".txt", "/tmp/car"+sys.argv[1]+".txt")
except:
    print "Error in distance computation."
    response_status(5,nomeArqResults)

print "Distance between house_bin.ppm and car.ppm = ", distance
response_status(0,nomeArqResults)

