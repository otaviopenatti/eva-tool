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

import os
from glob import glob
f = glob("/exp/otavio/img_databases/problem_images/Di*999*")
#bugado = f[0]
#limpo = None
#while 1:
    #try:
        #bugado.decode("utf-8")
    #except UnicodeDecodeError, ex:
        #bugado = bugado[:ex.start]+bugado[ex.end:]
    #else:
        #limpo = bugado
        #if (limpo != f[0]):
            #os.rename(f[0],limpo)
            #print "renomeou"
        #break

path = f[0]
path_str = path
while 1:
    try:
        print "verificando: ",path_str
        path_str.decode('utf-8')
        if (path_str != path):
            print "renomeando", path,"\n para", path_str
            os.rename(path,path_str)
            print "renomeou!"
        break
    except UnicodeDecodeError, ex:
        path_str = path_str[:ex.start]+path_str[ex.end:]
