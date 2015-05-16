# x86dump2dot
main.c -> a.out -> objdump -d -> dot -> png

# How To Use
gcc main.c -o main
objdump -d main >main.disas
dump2dot.php main.disas >main.dot
dot -Tpng main.dot -o main.pdf
